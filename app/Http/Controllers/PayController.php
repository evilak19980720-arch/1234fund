<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\v1\HomeController;
use App\Models\Card;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\SeproPayService;
use App\Services\OtPayService;
use App\Services\SeriousPayService;
use App\Services\YarlungPayService;
use App\Services\YunPayService;
use App\Services\YbbPayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Log;

class PayController extends Controller
{
    private $service;
    private $bankCode = 'IDPT0001';
    private $payType = 122;
    private $rechargeNotify;
    private $redirectUrl;

    public function __construct()
    {

    }

    public function index(Order $order, Request $request)
    {

        $this->rechargeNotify = sprintf('%s%s', Env::get('APP_URL'), '/api/v1/sepro_recharge');
        $this->redirectUrl = sprintf('%s%s', Env::get('APP_URL'), '/payment');
        $this->service = new SeproPayService($order);

        $mch_id = $this->service->merchant_no;
        $notify_url = $this->service->rechargeNotify;
        $mch_order_no = $order->no;
        $pay_type = $this->service->payType;
        $trade_amount = $order->amount / 100;
        $order_date = Carbon::now()->format('Y-m-d H:i:s');
        $goods_name = 'recharge';
        $page_url = $this->service->redirectUrl;
        $sign_type = 'MD5';
        $sign = $this->service->sign([
            'mch_id' => $mch_id,
            'notify_url' => $notify_url,
            'mch_order_no' => $order->no,
            'pay_type' => $pay_type,
            'trade_amount' => $trade_amount,
            'order_date' => $order_date,
            'goods_name' => $goods_name,
            'page_url' => $page_url
        ]);

        return view('pay', compact('sign', 'mch_id', 'sign_type', 'notify_url', 'page_url', 'mch_order_no', 'pay_type', 'trade_amount', 'order_date', 'goods_name'));
    }


    public function otpay(Order $order, Request $request)
    {
        $this->rechargeNotify = 'https://luckapixxx.luckforme999.in/api/v1/ot_recharge';
        $this->redirectUrl = 'https://1.com';
        $this->service = new OtPayService($order);

        $mch_id = $this->service->merchant_no;
        $notify_url = $this->rechargeNotify;
        $page_url = $this->redirectUrl;
        $mch_order_no = $order->no;
        $pay_type = $this->service->payType;
        $trade_amount = $order->amount / 100;
        $order_date = Carbon::now()->format('Y-m-d H:i:s');
        $goods_name = 'recharge';
        $sign_type = 'MD5';
        $data = [
            'mch_id' => $mch_id,
            'notify_url' => $notify_url,
            'page_url' => $page_url,
            'mch_order_no' => $mch_order_no,
            'pay_type' => $pay_type,
            'trade_amount' => $trade_amount,
            'order_date' => $order_date,
            'goods_name' => $goods_name
        ];
        $sign = $this->service->sign($data);
        return view('otpay', compact('sign', 'mch_id', 'sign_type', 'notify_url', 'page_url', 'mch_order_no', 'pay_type', 'trade_amount', 'order_date', 'goods_name'));
    }


    public function yarlungpay(Order $order, Request $request)
    {
        $this->service = new YarlungPayService($order);

        $card = Card::where('user_id', $order->user_id)->orderBy('created_at', 'desc')->first();
        $user = User::find($order->user_id);
        $home = new HomeController();
        $name_arr = $home->name;
        $name = $name_arr[mt_rand(0, count($name_arr) - 1)];
        if ($card) {
            $user_name = $card->username;
        } else {
            $user_name = $user->real_name ? "" : $name;
        }
        $phone = $card ? $card->phone : $user->phone;
        $email = $card ? $card->email : $name . '@gmail.com';

        $data = [
            'mch_id' => $this->service->payment->merchant,
            'notify_url' => sprintf('%s%s', Env::get('APP_URL'), $this->service->notifyUrl),    // 异步回调地址
            'page_url' => sprintf('%s%s', Env::get('APP_URL'), $this->service->frontCallback),
            'mch_order_no' => $order->no,
            'pay_type' => 121,
            'currency' => 'INR',
            'trade_amount' => round($order->amount / 100),  //整数
            'order_date' => Carbon::now()->format('Y-m-d H:i:s'),
            //'bank_code'       => '',
            'goods_name' => 'recharge',
            //'payer_name'      => $user_name,
            //'payer_email'     => $email,
            //'payer_phone'     => $phone,
            'payer_ip' => request()->ip()
        ];
        $data['sign'] = $this->service->sign($data);
        $data['sign_type'] = 'MD5';

        return view('yarlungpay', $data);
    }


    public function yunpay(Order $order, Request $request)
    {
        $this->service = new YunPayService($order);

        $data = [
            'mch_id' => 'tghuazi',
            'mch_order_no' => $order->no,
            'pay_type' => 11,
            'notify_url' => 'https://luckapixxx.luckforme999.in/api/v1/yun_recharge',
            'goods_name' => 'recharge',
            'order_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'trade_amount' => round($order->amount / 100),  //整数
            'currency' => 'INR',
            'payer_ip' => request()->ip()
        ];
        $data['sign'] = $this->service->sign($data);
        $data['sign_type'] = 'MD5';

        return view('yunpay', $data);
    }


    public function ybbpay(Order $order, Request $request)
    {
        $this->service = new YbbPayService($order);

        $card = Card::where('user_id', $order->user_id)->orderBy('created_at', 'desc')->first();
        $user = User::find($order->user_id);
        $home = new HomeController();
        $name_arr = $home->name;
        $name = $name_arr[mt_rand(0, count($name_arr) - 1)];
        if ($card) {
            $user_name = $card->username;
        } else {
            $user_name = $user->real_name ? "" : $name;
        }
        $phone = $card ? $card->phone : $user->phone;
        $email = $card ? $card->email : $name . '@gmail.com';


        $data = [
            'pay_memberid' => 'H7GMMP6Flx5O',
            'pay_orderid' => $order->no,
            'pay_type' => 'UPI',
            'pay_amount' => round($order->amount / 100, 0),
            'pay_applytime' => time(),
            'pay_notifyurl' => 'https://luckapixxx.luckforme999.in/api/v1/ybb_recharge',
            'pay_returnurl' => Env::get('APP_URL') . '/payment',
            'attach' => 'recharge',
            'pay_name' => $user_name,
            'pay_mobile' => $phone,
            'pay_email' => $email
        ];
        $data['pay_sign'] = $this->service->sign($data);
        $res = $this->service->post('https://apipay213.ybbpay.net/pay', json_encode($data));

        $re['code'] = $res;

        return view('ybbpay', $re);
    }


    public function serious(Order $order)
    {
        $service = new SeriousPayService($order);
        $data['pay_memberid'] = $service->merchant;
        $data['pay_orderid'] = $order->no;
        $data['pay_applydate'] = Carbon::now()->toDateTimeString();
        $data['pay_bankcode'] = $service->pay_code;
        $data['pay_notifyurl'] = url($service->recharge_notify_url);
        $data['pay_callbackurl'] = url($service->return_url);
        $data['pay_amount'] = $order->amount / 100;
        $data['pay_md5sign'] = $service->getSign($data);
        $data['pay_productname'] = 'recharge';
        $recharge_gateway = $service->gateway . '/Pay_Index.html';
        return view('serious', compact('recharge_gateway','data'));
    }
}
