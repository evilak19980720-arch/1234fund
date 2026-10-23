<?php

namespace App\Http\Controllers\Api\v1;

use App\Handlers\KeysHandler;
use App\Http\Requests\Api\v1\PayRequest;
use App\Http\Requests\Api\v1\WithdrawalRequest;
use App\Http\Resources\PaymentThirdPayResource;
use App\Logic\PayLogic;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentThirdCode;
use App\Models\PaymentThirdPay;
use App\Models\Vip;
use App\Services\Pay\PayFactory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\UserRechargeAddress;
use App\Jobs\Urecharge;
use App\Models\Country;
use App\Models\Currency;

/**
 * 支付
 * @package App\Http\Controllers\Api\v1
 */
class PayController extends Controller
{
    private $logic;

    public function __construct()
    {
        $this->logic = new PayLogic();
    }

    //获取支持货币列表
    public function currencies(Request $request){
        $user = $request->user();
        if(empty($user->country)){
            $user->country = "US";
        }
        //dump($user->country);
        $country = Country::getCountries($user->country);
        //dump(explode(',',$country->currery));
        $currencies = Currency::getCurrencies(explode(',',$country->currery));
        //dump($currencies);
        return $this->success($currencies);
    }

    public function recharge_address(PayRequest $request){
        $user = $request->user();
        $currery = $request->currery; //	USDT-TRC
        $amount = (int)$request->amount;

        $platform = $currery;
        $curreryObj = Currency::getCurrencies($currery);
        if(empty($curreryObj)){
            $this->errorResponse(Response::HTTP_OK, transtable('60016'), 60016);
        }
        $actual_amount = bcmul($amount, $curreryObj->exchange_rate);

        $min = Configuration::getItem('recharge_min');
        if ($amount < $min) {
            $this->errorResponse(Response::HTTP_OK, sprintf(transtable('60011'), $min / 100), 60011);
        }

        $user_order = Order::where('user_id','=',$user->id)
            ->where('type','=',Account::RECHARGE)
            ->where('state','=',0)
            ->where('actual_amount','=',$actual_amount)
            ->where('platform','=',$platform)
            ->orderByDesc('id')->first();
        if($user_order){
            if(Cache::get(sprintf(KeysHandler::USDTCHECK,$currery,$user->id)) != 1){
                Urecharge::dispatch($user->id, ['currery' => $currery])->delay(10)->onQueue(env('REDIS_QUEUE', 'default'));
                Cache::put(sprintf(KeysHandler::USDTCHECK,$currery,$user->id), '1',10*60);
            }
        }else{
            //保存一条失败的充值记录
            $order = new Order();
            $order->platform = $platform;
            $order->payment_id = 0;
            $order->user_id = $user->id;
            $order->amount = $amount;
            $order->actual_amount = $actual_amount;
            $order->no = only_code();
            $order->type = Account::RECHARGE;
            $order->save();
        }
        $data['recharge_address'] = UserRechargeAddress::getRechargeAddress($user->id, $currery);
        return $this->success($data);
    }

    public function recharge_usdt(Request $request){
        $user = $request->user();
        $currery = $request->currery; //	USDT-TRC
        //后期可优化兼容多个链
        if(Cache::get(sprintf(KeysHandler::USDTCHECK,$currery,$user->id)) != 1){
            Urecharge::dispatch($user->id, ['currery' => $currery])->delay(3)->onQueue(env('REDIS_QUEUE', 'default'));
            Cache::put(sprintf(KeysHandler::USDTCHECK,$currery,$user->id), '1',10*60);  //只查10分钟
        }

        return $this->success();
    }


    // 充值
    public function recharge(PayRequest $request)
    {
        $user = $request->user();
        $currery = $request->currery;
        $amount = $request->get('amount');
        $host = $request->get('host',"");
        $code_id = $request->get('code_id');

        $min = Configuration::getItem('recharge_min');
        if ($amount < $min) {
            $this->errorResponse(Response::HTTP_OK, sprintf(transtable('60011'), $min / 100), 60011);
        }

        if($code_id){
            $payment_third_code = PaymentThirdCode::query()->open()->whereHas('payment', function ($q)use($currery) {
                $q->open()->pay(Payment::PAY_OPEN)->where('currery','=',$currery );
            })->with(['payment'])->where('id',$code_id)->first();
        }else{
            $payment_third_code = PaymentThirdCode::query()->open()->whereHas('payment', function ($q)use($currery) {
                $q->open()->pay(Payment::PAY_OPEN)->where('currery','=',$currery);
            })->with(['payment'])->first();
        }

        if (!$payment_third_code) {
            $this->errorResponse(Response::HTTP_OK, transtable('60000'), 60000);
        }

        $curreryObj = Currency::getCurrencies($currery);
        if(empty($curreryObj)){
            $this->errorResponse(Response::HTTP_OK, transtable('60016'), 60016);
        }
        //dump($curreryObj->exchange_rate);
        $amount_exchange_rate = bcmul($amount, $curreryObj->exchange_rate);
        //dump($amount_exchange_rate);
        if ($amount_exchange_rate > $payment_third_code->upper) {
            $this->errorResponse(Response::HTTP_OK, sprintf(transtable('60012'), $payment_third_code->upper / 100), 60012);
        }
        if ($amount_exchange_rate < $payment_third_code->lower) {
            $this->errorResponse(Response::HTTP_OK, sprintf(transtable('60011'), $payment_third_code->lower / 100), 60011);
        }
        // 生成本地订单
        $order = new Order();
        $order->platform = $payment_third_code->payment->server_name;
        $order->payment_id = $payment_third_code->id;
        $order->user_id = $user->id;
        $order->amount = $amount;
        $order->actual_amount = $amount_exchange_rate;
        $order->no = only_code();
        $order->type = Account::RECHARGE;
        $order->host = $host;
        $order->save();

        // 生成远程订单
        $url = PayFactory::service(ucfirst($order->platform), $order)->recharge($order);
        if ($url) {
            return $this->success([
                'url' => $url,
                're_url' => Env::get('URL')
            ]);
        }
        $this->errorResponse(Response::HTTP_OK, transtable('40001'), 40001);
    }


    //获取支持货币列表
    public function withdrawalCurrencies(Request $request){
        $user = $request->user();
        if(empty($user->country)){
            $user->country = "US";
        }
        //dump($user->country);
        $country = Country::getCountries($user->country);
        //dump(explode(',',$country->currery));
        $currencies = Currency::getWithdrawalCurrencies(explode(',',$country->currery));
        /*
        //判断是否有对应的银行卡
        $cards = $user->cards;
        foreach ($currencies as &$currency){
            $currency->card = (object)[];
            foreach ($cards as $card){
                $currency_code = $currency->code;
                if($currency_code == 'USDT-TRC'){
                    $currency_code = 'TRX'; //使用同一个卡
                }
                if($currency_code == $card->currery){
                    $currency->card = $card;
                }
            }
        }
        */
        //dump($currencies);
        return $this->success($currencies);
    }


    /**
     * 获取用户提现配置
     * @param Request $request
     * @return mixed
     */
    public function withdrawalFee(Request $request)
    {
        $user = $request->user();
        $card = $user->card;
        $curreryObj = Currency::getAllCurrencies($card->currery);
        if(empty($curreryObj)){
            $this->errorResponse(Response::HTTP_OK, transtable('60016'), 60016);
        }

        $withdrawal_fee = $request->user()->vip->withdrawal_fee;
        $withdrawal_min = Configuration::getItem('withdrawal_min');


        $country = Country::getCountries($user->country);
        if($country && $country->first_withdrawal_free == 1){
            $hasSuccessfulWithdrawal = Order::where('user_id', $user->id)
                ->where('type', Account::WITHDRAWAL)
                ->whereIn('state', [Order::SUCCESS,Order::REVIEW,Order::PENDING])
                ->exists();
                if (!$hasSuccessfulWithdrawal) {
                        $withdrawal_fee = 0;
                }
        }
        if($country->withdrawal_amount > 0){
            $withdrawal_min = $country->withdrawal_amount;
        }


        return $this->success([
            'withdrawal_fee' => $withdrawal_fee,
            'withdrawal_min' => $withdrawal_min,
            'withdrawal_max' => Configuration::getItem('withdrawal_max'),
            'card' => $card,
            'curreryObj' => $curreryObj
        ]);
    }

    // 提现
    public function withdrawal(WithdrawalRequest $request)
    {
        $user = $request->user();

        $min = Configuration::getItem('withdrawal_min');
        $max = Configuration::getItem('withdrawal_max');
        if ($request->get('amount') < $min || $request->get('amount') > $max) {
            $this->errorResponse(Response::HTTP_OK, sprintf(transtable('60005'), $min / 100, $max / 100), 60005);
        }


        $country = Country::getCountries($user->country);
        if($country->withdrawal_amount > 0 && $request->get('amount') < $country->withdrawal_amount){
            $this->errorResponse(Response::HTTP_OK, sprintf(transtable('60005'), $country->withdrawal_amount / 100, $max / 100), 60005);
        }


        // 提现倍数
        /*
        $multiple = Configuration::getItem('withdrawal_amount');
        if ($request->get('amount') % $multiple !== 0) {
            $this->errorResponse(Response::HTTP_OK, sprintf(transtable('40004'), $multiple), 40004);
        }
        */

        //余额
        if ($request->get('amount') > $user->balance) {
            $this->errorResponse(Response::HTTP_OK, transtable('10016'), 10016);
        }


        //银行卡
        //$card = $user->cards()->first();
        $card = $user->card;
        if (!$card) {
            $this->errorResponse(Response::HTTP_OK, transtable('40002'), 40002);
        }
        $curreryObj = Currency::getAllCurrencies($card->currery);
        if(empty($curreryObj)){
            $this->errorResponse(Response::HTTP_OK, transtable('60016'), 60016);
        }



        //非工作日不能提现
        /*
        if (Carbon::now()->isWeekend()) {
            $this->errorResponse(Response::HTTP_OK, trans('error.40007'), 40007);
        }
        */

        /*if (null != Order::where('closed', '=', 0)->where('user_id', '=', $user->id)->value('id')) {
            $this->errorResponse(Response::HTTP_OK, trans('error.60009'), 60009);
        }*/

        /*
        提现:
        查上一次的充值金额A 和时间T ,判断时间T到当前时间段 总消费(买入或预约)金额B>=A 则可以提现
         */
        $lastRecharge = Account::where('to', '=', $user->id)->where('type', '=', Account::RECHARGE)->where('order_id', '<>', 0)->orderBy('id', 'desc')->first();
        if ($user->hierarchy_id == 1 && $lastRecharge) {
            $amount = Account::where('from', '=', $user->id)
                //->whereIn('type', [11, 12, 20])
                ->whereIn('type', [11])
                ->where('created_at', '>=', $lastRecharge->created_at)
                ->sum('amount');
            if ($lastRecharge->amount > abs($amount)) {
                $this->errorResponse(Response::HTTP_OK, trans('error.40006'), 40006);
            }
        }

        $vip = Vip::getVip($user->vip_id);
        $today_orders = Order::query()
            ->where('type', '=', Order::WITHDRAWAL)
            ->where('user_id', '=', $user->id)
            ->whereDate('created_at', '=', Carbon::today())
            ->get();
        //每日提现次数判断
        if ($user->hierarchy_id == 1 && $today_orders->count() >= $vip->daily_cash_out) {
            $this->errorResponse(Response::HTTP_OK, sprintf(transtable('40010'), $vip->level, $vip->daily_cash_out), 40010);
        }
        //单次提现上限判断
        if ($user->hierarchy_id == 1 && $request->get('amount') > $vip->single_withdrawal_amount) {
            return $this->fail(60015, sprintf(transtable('60015'), $vip->level, $vip->single_withdrawal_amount / 100));
        }
        //每日提现上限判断
        if ($user->hierarchy_id == 1 && $today_orders->pluck('amount')->sum() + $request->get('amount') > $vip->daily_withdrawal_amount) {
            return $this->fail(60014, sprintf(transtable('60014'), $vip->level, $vip->daily_withdrawal_amount / 100));
        }


        //增加验证会员激活状态
        if ($user->activation != 1) {
            // 如果未激活，检查设备号是否存在
            if (empty($user->device_number)) {
                $this->errorResponse(Response::HTTP_OK, transtable('60017'), 60017);
            }

            // 调用接口查询设备号是否存在
            $deviceExists = checkDeviceExists($user->device_number);

            if ($deviceExists === null) {
                // 查询失败
                $this->errorResponse(Response::HTTP_OK, transtable('60017'), 60017);
            } elseif ($deviceExists === true) {
                // 设备号存在，更新激活状态
                $user->activation = 1;
                $user->save();
            } else {
                // 设备号不存在，返回错误
                $this->errorResponse(Response::HTTP_OK, transtable('60017'), 60017);
            }
        }

        $res = app(Order::class)->createWithdrawOrder($user, $request->get('amount'), $curreryObj, $card);
        if ($res == 1) {
            return $this->success();
        }
        return $this->fail($res, transtable($res));
    }


    /**
     *
     * 支付方式
     * @return JsonResponse|object
     */
    public function payment(Request $request)
    {

        $currery = $request->currery;

        $payment = Cache::remember(sprintf(KeysHandler::PAYMENT_THIRD_PAY_LIST,$currery),300, function ()use($currery) {
            return Payment::query()->where('currery','=',$currery)->open()->pay(Payment::PAY_OPEN)
                ->whereHas ('payment_third_code',function($query){
                    $query->where('is_open', 1);
                })
                ->with(['payment_third_code'=>function($query){
                    $query->select('id','payment_third_pay_id','title','upper','lower')->where('is_open', 1)->orderBy('sort','asc');
                }])
                ->select(['id','title'])
                //->orderBy('sort','asc')
                ->inRandomOrder()
                ->get();
        });
        return $this->success($payment);


    }



}
