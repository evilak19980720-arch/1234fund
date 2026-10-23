<?php

namespace App\Models;

use App\Events\UserRechargeEvent;
use App\Handlers\KeysHandler;
use App\Jobs\CheckTrcBalance;
use App\Jobs\CheckBscBalance;
use App\Jobs\PayOrder;
use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * 订单
 * @package App\Models
 */
class Order extends Model
{
    use DefaultDatetimeFormat;

    //显示状态
    const HIDE = 0;//隐藏
    const SHOW = 1;//显示

    const WITHDRAWAL = 0;//提现
    const RECHARGE = 1;//充值

    // 订单类型
    const ORDER_TYPE = [
        'withdrawal' => 0,
        'recharge' => 1
    ];

    const REVIEW = -1;  // 审核
    const PENDING = 0;   // 进行中
    const SUCCESS = 1;   // 成功
    const FAILED = 2;   // 失败
    const CANCEL = 3;   // 取消
    const REFUSE = 4;   // 拒绝

    protected $fillable = [
        'user_id', 'platform', 'amount', 'no', 'payment_no', 'paid_at', 'closed', 'type', 'state','isevent','host','currery_amount','currery','card','isfirstRecharge'
    ];

    // 记录
    public function logs()
    {
        return $this->hasMany(OrderLog::class);
    }

    // 明细
    public function Account()
    {
        return $this->hasOne(Account::class);
    }

    // 明细
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function payment_third_code_recharge()
    {
        return $this->belongsTo(PaymentThirdCode::class,'payment_id','id')->with('payment');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ip_registers()
    {
        return $this->hasMany(User::class, '');
    }

    /**
     * 完成支付
     * @param $state
     * @param $no
     * @param $paymentNo
     * @param $paidAt
     * @param $response
     * @return mixed
     */
    public function rechargeComplete($state, $no, $paymentNo, $paidAt, $response): bool
    {
        return DB::transaction(function () use ($state, $no, $paymentNo, $paidAt, $response) {
            $order = $this->where('no', '=', $no)
                ->where('closed', '=', 0)
                ->lockForUpdate()
                ->first();
            if (!$order) {
                return false;
            }
            // 如果有充值流水则停止
            if (Account::where('order_id', '=', $order->id)->where('type', '=', Account::RECHARGE)->lockForUpdate()->value('id')) {
                return false;
            }
            // 关闭订单
            $order->state = $state;
            $order->paid_at = $paidAt;
            $order->closed = 1;
            $order->payment_no || $order->payment_no = $paymentNo;
            $order->notify_response = $response;
            $order->save();
            if ($state == self::SUCCESS) {
                $user = User::where('id', '=', $order->user_id)->lockForUpdate()->first();
                // +钱
                $user->increment('balance', $order->amount);
                // 记账
                Account::create([
                    'order_id' => $order->id,
                    'type' => Account::RECHARGE,
                    'to' => $order->user_id,
                    'from' => 0,
                    'amount' => $order->amount,
                    'level' => 0
                ]);

                self::pushFirstMes($order);
                if($order->platform == 'TRX' || $order->platform == 'USDT-TRC'){
                    CheckTrcBalance::dispatch(UserRechargeAddress::getRechargeAddress($user->id))->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                }
                if($order->platform == 'USDT-BSC'){
                    CheckBscBalance::dispatch(UserRechargeAddress::getRechargeAddress($user->id , 'USDT-BSC'))->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                }
                //充值成功的事件
                event(new UserRechargeEvent($order));
            }

            return true;
        });
    }


    /**
     * 创建提现订单
     * @param $user
     * @param $amount
     * @return mixed
     */
    public function createWithdrawOrder($user, $amount, $curreryObj, $card)
    {
        return DB::transaction(function () use ($user, $amount, $curreryObj, $card) {
            $user = User::query()->where('id', '=', $user->id)->lockForUpdate()->first();
            if ($amount > $user->balance) {
                return 10016;
            }
            $order = new Order();

            $withdrawal_fee = $user->vip->withdrawal_fee;
            //$handlingfee = floor($amount / 100 * $withdrawal_fee / 100) * 100;
            $handlingfee = $withdrawal_fee * 100;

            $country = Country::getCountries($user->country);
            if($country && $country->first_withdrawal_free == 1){
                //手续费计算
                //提现手续费百分比改为利用会员等级去vip配置获取对应的百分比
                //判断是否是首次提现（查询是否有已成功的提现订单）
                $hasSuccessfulWithdrawal = self::where('user_id', $user->id)
                    ->where('type', Account::WITHDRAWAL)
                    ->whereIn('state', [self::SUCCESS,self::REVIEW,self::PENDING])
                    ->exists();
                    if (!$hasSuccessfulWithdrawal) {
                           $withdrawal_fee = 0;
                           $handlingfee = 0;
                    }
            }


            $actual_amount = $amount - $handlingfee;

            if($actual_amount < 0){
                return 10016;
            }

            $order->currery = $curreryObj->code;
            $order->currery_amount = bcmul($actual_amount, $curreryObj->exchange_rate);
            $order->card = json_encode($card, JSON_UNESCAPED_UNICODE);
            //'card' => json_encode($card, JSON_UNESCAPED_UNICODE)
            // 生成本地订单
            $order->user_id = $user->id;
            $order->amount = $amount;
            $order->withdrawal_fee = $withdrawal_fee;
            $order->handlingfee = $handlingfee;
            $order->actual_amount = $actual_amount;
            $order->no = only_code();
            $order->type = Account::WITHDRAWAL;
            $order->state = Order::REVIEW;
            $order->save();
            // 扣余额
            $user->decrement('balance', $order->amount);
            $user->increment('frozen', $order->amount);
            // 记录
            $account = new Account();
            $account->order_id = $order->id;
            $account->type = Account::WITHDRAWAL;
            $account->to = 0;
            $account->from = $order->user_id;
            $account->amount = $amount * -1;
            $account->level = 0;
            $account->save();

            // 将设备检查任务推入队列
            \App\Jobs\CheckWithdrawalDeviceJob::dispatch($order->id)->delay(30)->onQueue(env('REDIS_QUEUE', 'default'));

            return 1;
        });
    }


    /**
     * 拒绝提现
     *
     * @param Order $order
     * @return void
     */
    public static function refuseWithdrawal($order)
    {
        DB::transaction(function () use ($order) {
            // 锁定订单
            $order = self::where('id', $order->id)
                ->where('state', self::REVIEW)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                // 订单已被处理，跳过
                return;
            }

            // 更新订单状态
            $order->state = self::REFUSE;
            $order->closed = 1;
            $order->remark = 'Fake user';
            $order->save();

            // 减少冻结金额
            User::where('id', $order->user_id)->decrement('frozen', $order->amount);
        });
    }



    /**
     * 完成提现
     * @param array $data 关键数据
     * @param array $param 回调参数
     * @return mixed
     */
    public function withdrawComplete(array $data, array $param)
    {
        return DB::transaction(function () use ($data, $param) {
            if (isset($data['paymentNo'])) {
                $order = $this->where('payment_no', '=', $data['paymentNo'])
                    ->where('no', '=', $data['orderId'])
                    ->lockForUpdate()
                    ->where('closed', '=', 0)
                    ->first();
            } else {
                //info('CLOUD提现回调进入完成提现方法：'.$data['orderId']);
                $order = $this
                    ->where('no', '=', $data['orderId'])
                    ->where('closed', '=', 0)
                    ->lockForUpdate()
                    ->first();
            }

            if (!$order) {
                return false;
            }
            //info('CLOUD提现回调进入完成提现方法后查询订单完成：'.$data['orderId']);
            $order->state = $data['state'];
            $order->paid_at = $data['paidAt'];
            $order->notify_response = json_encode($param, JSON_UNESCAPED_UNICODE);
            $order->closed = 1;  //成功订单状态改为关闭,其它状态 不关闭
            if ($data['state'] == self::SUCCESS) {

                // 减冻结
                User::where('id', '=', $order->user_id)->decrement('frozen', $order->amount);
            } else {
                //info('CLOUD提现回调进入完成提现方法-订单失败：'.$data['orderId']);
                $order = self::withdrawFail($order);
            }
            $order->save();
            return true;
        });
    }


    /*
     * 提现失败
     * */
    static public function withdrawFail($order)
    {
        //info('CLOUD提现回调进入完成提现方法-订单失败withdrawFail：'.$order->no);
        User::where('id', '=', $order->user_id)->decrement('frozen', $order->amount);  // 减冻结
        User::where('id', '=', $order->user_id)->increment('balance', $order->amount);  // 加余额
        Account::create([
            'order_id' => $order->id,
            'type' => Account::WITHDRAWAL_FAIL,
            'to' => $order->user_id,
            'form' => 0,
            'amount' => $order->amount,
            'level' => 0
        ]);
        return $order;
    }

    /*
     * 用户取消提现
     * */
    static public function withdrawCancel($order_id)
    {
        return DB::transaction(function () use ($order_id) {
            $order = Order::where('id', '=', $order_id)->where('state', '=', '-1')->lockForUpdate()->first();
            if ($order) {
                $order->state = Order::CANCEL;
                $order->closed = 1;  //关闭订单
                $order->remark = 'User canceled';  //默认备注
                $order->save();
                User::where('id', '=', $order->user_id)->decrement('frozen', $order->amount);  // 减冻结
                User::where('id', '=', $order->user_id)->increment('balance', $order->amount);  // 加余额
                Account::create([
                    'order_id' => $order->id,
                    'type' => Account::WITHDRAWAL_FAIL,
                    'to' => $order->user_id,
                    'form' => 0,
                    'amount' => $order->amount,
                    'level' => 0
                ]);
                // 总提现次数-1
                User::where('id', '=', $order->user_id)->decrement('cash_out_times', 1);
                return true;
            } else {
                return false;
            }

        });
    }

    public static function pushFirstMes($order){
        $user_id = $order->user_id;
        $amount = $order->amount;
        $no = $order->no;

        $recharge_num = Order::where('state',1)->where('type',1)->where('user_id',$user_id)->count();
        if($recharge_num == 1){
            $order->isfirstRecharge = 1;
            $order->save();
            self::firstRechargeSuccess($user_id, $amount, $no);
        }
        else{
            Redis::rpush("mailMessage:".$user_id,json_encode([
                "event"=>"rechargeSuccess", //firstRechargeSuccess
                "amount"=>$amount / 100,
                "no" => $no
            ]));
        }

    }


    public static function firstRechargeSuccess($user_id, $amount, $no){
        Redis::rpush("mailMessage:".$user_id,json_encode([
            "event"=>"firstRechargeSuccess", //firstRechargeSuccess
            "amount"=>$amount / 100 ,
            "no" => $no
        ]));
    }


    //查询会员充值总额
    //可又换为缓存查询
    public static function getUserTotalRecharge($user_id,$date = null){
        $total_query = self::query()
            ->where('user_id','=',$user_id)
            ->where('type','=',self::RECHARGE)
            ->where('state','=',self::SUCCESS);
        //->whereDate('created_at','>',Carbon::parse('2024-09-01'))
        $total = $total_query->sum('amount');
        return $total;
    }

    //用户充值次数
    public static function getUserRechargeCount($user_id,$date = null){
        $total_query = self::query()
            ->where('user_id','=',$user_id)
            ->where('type','=',self::RECHARGE)
            ->where('state','=',self::SUCCESS);
        //->whereDate('created_at','>',Carbon::parse('2024-09-01'))
        $total = $total_query->sum('amount');
        return $total;
    }


}
