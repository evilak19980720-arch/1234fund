<?php

namespace App\Models;

use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class FundOrder extends Model
{
    use DefaultDatetimeFormat;
    protected $fillable = ['fund_period_id','user_id','quantity','amount','win','buy'];

    //是否中奖
    const WIN_NO = 0;//否
    const WIN_YES = 1;//是

    //处理方式
    const UNTREATED = 0;//未处理
    const REARING = 1;//饲养
    const TRANSFER = 2;//转让

    //用户
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //彩种排期
    public function fundPeriod()
    {
        return $this->belongsTo(FundPeriod::class);
    }


    /**
     * 获取指定排期的购买记录    这里用户获取当前最新一期的购买记录
     * @param int $fund_period_id
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function record(int $fund_period_id)
    {
        $record = self::query()->with('user:id,phone,avatar,hidephone')
            ->where('fund_period_id','=',$fund_period_id)
            ->select('id','user_id','quantity','buy','amount','created_at')
            ->latest()
            ->get()->toArray();
        $fund_period = FundPeriod::query()->where('id','=',$fund_period_id)->first();
        if(count($record) < 10 && time() > Carbon::parse($fund_period->end_time)->timestamp) {
            $record = array_merge($record,$this->fakeFundOrderRecord($fund_period_id));
        }
        return $record;
    }


    /**
     * 生成假的购买数据
     * @param int $fund_period_id
     * @return mixed
     */
    public function fakeFundOrderRecord(int $fund_period_id)
    {
        return Cache::remember('fund_order_record_'.$fund_period_id, 24 * 60 * 60, function () use($fund_period_id) {
            $total_price = FundPeriod::query()->where('id','=',$fund_period_id)->value('total_price');
            $users = User::query()->where('hierarchy_id','=',4)->limit(random_int(10,20))->select('id','phone','avatar','hidephone')->get()->toArray();
            $data = [];
            foreach ($users as $key => $user) {
                $item_data['id'] = $key;
                $item_data['user_id'] = $user['id'];
                $quantity = random_int(1,10);
                $item_data['quantity'] = $quantity;
                $buy_type = array_merge(range(0,9),['big','small']);
                shuffle($buy_type);
                $buy = Arr::first($buy_type);
                $item_data['buy'] = $buy;
                $item_data['amount'] = FundPeriod::getBuyPrice($buy,$total_price) * $quantity;
                $item_data['created_at'] = date('Y-m-d H:i:s');
                $item_data['user'] = $user;
                $item_data['user']['hidephone'] = 1;
                $data[] = $item_data;
            }
            return $data;
        });
    }
//Fake data


    /**
     *
     * 购买众筹
     * @param FundPeriod $fundPeriod        众筹排期
     * @param User $user                    用户
     * @param int $buy_number               购买的数字
     * @param int $number_quantity          购买数字的数量
     * @param int $buy_number_price         购买数字的单价
     * @param string $buy_size              购买的大小
     * @param int $size_quantity            购买大小的数量
     * @param int $buy_size_prize           购买大小的单价
     */
    public function buys(FundPeriod $fundPeriod,User $user,int $buy_number,int $number_quantity,int $buy_number_price,string $buy_size,int $size_quantity,int $buy_size_prize)
    {
        DB::transaction(function () use ($fundPeriod,$user,$buy_number,$number_quantity,$buy_number_price,$buy_size,$size_quantity,$buy_size_prize){
            //购买了数字
            $total = 0;
            $buy_number_model = new FundOrder();
            $bug_size_model = new FundOrder();
            if ($buy_number != -1 && $number_quantity > 0) {
                $amount = $buy_number_price * $number_quantity;
                $total += $amount;
                //1.增加购买记录
                $buy_number_model = self::query()->create([
                    'fund_period_id' => $fundPeriod->id,
                    'user_id' => $user->id,
                    'quantity' => $number_quantity,
                    'amount' => $amount,
                    'win'    => $this->isWin($fundPeriod,$buy_number),
                    'buy'    => $buy_number
                ]);
            }
            //购买了大小
            if ($buy_size != 'no' && $size_quantity > 0) {
                $amount = $buy_size_prize * $size_quantity;
                $total += $amount;
                //1.增加购买记录
                $bug_size_model = self::query()->create([
                    'fund_period_id' => $fundPeriod->id,
                    'user_id' => $user->id,
                    'quantity' => $size_quantity,
                    'amount' => $amount,
                    'win'    => $this->isWin($fundPeriod,$buy_size),
                    'buy'    => $buy_size,
                ]);
            }
            //2.扣除余额
            User::query()->where('id','=',$user->id)->decrement('balance',$total);
            //3.增加余额流水
            Account::query()->create([
                'type' => Account::FUNDING_BUY,
                'to' => 0,
                'from' => $user->id,
                'amount' => $total * -1,
                'level' => 0
            ]);
            //4.按配置比例解冻众筹冻结金额
            (new FundAmountAccount())->handle([$buy_number_model->toArray(),$bug_size_model->toArray()],$user);
        });
    }


    /**
     * 转让
     * @param int $fund_order_id     购买记录id
     */
    public function transfer(int $fund_order_id)
    {
        DB::transaction(function () use ($fund_order_id) {
            $fund_order = self::query()->with('fundPeriod.fund')
                ->where('id','=',$fund_order_id)
                ->where('status','=',FundOrder::UNTREATED)
                ->where('win','=',FundOrder::WIN_YES)
                ->lockForUpdate()
                ->first();
            if ($fund_order) {
                $total_price = $fund_order->fundPeriod->total_price * $fund_order->quantity;
                $price_rate = intval($total_price * $fund_order->fundPeriod->fund->rate * 0.01);  //手续费
                $price = $total_price - $price_rate;
                //增加用户余额
                User::query()->where('id','=',$fund_order->user_id)->increment('balance', $price);
                //添加流水记录
                Account::query()->create([
                    'type' => Account::TRANSFER,
                    'to' => $fund_order->user_id,
                    'amount' => $price,
                    'order_id' => $fund_order->id
                ]);
                $fund_order->status = self::TRANSFER;
                $fund_order->save();
            }
        });
    }

    /**
     * 饲养
     * @param int $fund_order_id    购买记录id
     */
    public function rearing(int $fund_order_id)
    {
        DB::transaction(function () use ($fund_order_id) {
            $fund_order = self::query()->with('fundPeriod.fund')
                ->where('id','=',$fund_order_id)
                ->where('status','=',FundOrder::UNTREATED)
                ->where('win','=',FundOrder::WIN_YES)
                ->lockForUpdate()
                ->first();
            $price = $fund_order->fundPeriod->total_price;
            $total_price = $price * $fund_order->quantity;
            $profit_amount = $fund_order->fundPeriod->profit * $fund_order->quantity;
            $selling_price = $price + $fund_order->fundPeriod->profit;
            $selling_amount = $selling_price * $fund_order->quantity;
            $data = [
                'user_id' => $fund_order->user_id,
                'pet_id' => $fund_order->fundPeriod->pet_id,
                'price' => $price,
                'number' => $fund_order->quantity,
                'amount' => $total_price,
                'profit_amount' => $profit_amount,
                'selling_price' => $selling_price,
                'selling_amount' => $selling_amount,
                'day' => $fund_order->fundPeriod->day,
                'type' => 1,
                'state' => 0,
                'source' => 1,
                'no' => only_code(),
                'start_at'  => date('Y-m-d')
            ];
            //创建订单
            PetOrder::query()->create($data);
            $fund_order->status = self::REARING;
            $fund_order->save();
        });
    }

    /**
     * 获取用户是否中奖
     * @param FundPeriod $fundPeriod
     * @param string $buy
     * @return int
     */
    public function isWin(FundPeriod $fundPeriod,string $buy):int
    {
        if (is_numeric($buy)) {
            //购买的数字
            return (int)$buy === $fundPeriod->draw_number ? 1 : 0;
        } else {
            //购买的大小
            return $buy === $fundPeriod->draw_size ? 1 : 0;
        }
    }


    //更新是否中奖
    public function updateIsWin($fund_period_id)
    {
        $fund_period = FundPeriod::query()->where('id','=',$fund_period_id)->first();
        foreach ($fund_period->fundOrder as $item) {
            $is_win = self::isWin($fund_period,$item->buy);
            if ($is_win != $item->win) {
                $item->win = $is_win;
                $item->save();
            }
        }
    }

}
