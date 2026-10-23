<?php

namespace App\Services\Pay;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentThirdCode;
use Illuminate\Support\Facades\DB;

class PayService
{
    /**
     * 支付模型
     */
    public $payment;


    /**
     * 代收接口地址
     * @var string
     */
    public string $pay_url;

    /**
     * 代收查询接口地址
     * @var string
     */
    public string $pay_query_url;


    /**
     * 代付接口地址
     * @var string
     */
    public string $withdrawal_url;

    /**
     * 代付查询接口地址
     * @var string
     */
    public string $withdrawal_query_url;

    /**
     * 代收通道
     * @var string
     */
    public string $pay_code;

    /**
     * 代付通道
     * @var string
     */
    public string $withdrawal_code;

    /**
     * 商户号
     * @var string
     */
    public string $merchant;

    /**
     * 代收秘钥
     * @var string
     */
    public string $pay_key;

    /**
     * 代付秘钥
     * @var string
     */
    public string $withdrawal_key;

    /**
     * 同步跳转地址
     * @var string
     */
    public string $return_url;

    /**
     * 充值/提现订单
     * @var Order
     */
    public Order $order;



    public string $password;


    /**
     * 平台公钥
     * @var string
     */
    public string $public_key;

    /**
     * 商户私钥
     * @var string
     */
    public string $private_key;

    public function __construct($order)
    {
        if ($order instanceof Order) {
            $this->order = $order;
            $payment_id = $this->order->payment_id;
        } else {
            dd('未配置数据类型');
        }
        if($this->order->type == 0){
            $this->payment = Payment::query()->where('id', '=', $payment_id)->first();
            $this->pay_url = $this->payment->pay_url;
            $this->pay_query_url = $this->payment->pay_query_url;
            $this->withdrawal_url = $this->payment->withdrawal_url;
            $this->withdrawal_query_url = $this->payment->withdrawal_query_url;
            $this->pay_code = $this->payment->pay_code;
            $this->withdrawal_code = $this->payment->withdrawal_code;
            $this->merchant = $this->payment->merchant;
            $this->pay_key = $this->payment->pay_key;
            $this->withdrawal_key = $this->payment->withdrawal_key;
            $this->password = $this->payment->password;
            $this->public_key = (string)$this->payment->public_key;
            $this->private_key = (string)$this->payment->private_key;
            $this->currery = $this->payment->currery;
        }else{
            $this->payment_third_pay = PaymentThirdCode::query()->with('payment')->where('id',$payment_id)->first();
            $this->pay_url = $this->payment_third_pay->payment->pay_url;
            $this->pay_query_url = $this->payment_third_pay->payment->pay_query_url;
            $this->withdrawal_url = $this->payment_third_pay->payment->withdrawal_url;
            $this->withdrawal_query_url = $this->payment_third_pay->payment->withdrawal_query_url;
            $this->withdrawal_code = $this->payment_third_pay->payment->withdrawal_code;
            $this->merchant = $this->payment_third_pay->payment->merchant;
            $this->pay_key = $this->payment_third_pay->payment->pay_key;
            $this->withdrawal_key = $this->payment_third_pay->payment->withdrawal_key;
            $this->password = $this->payment_third_pay->payment->password;
            $this->public_key = (string)$this->payment_third_pay->payment->public_key;
            $this->private_key = (string)$this->payment_third_pay->payment->private_key;

            $this->pay_type = $this->payment_third_pay->pay_type;
            $this->pay_code = $this->payment_third_pay->pay_code;
            $this->currery = $this->payment_third_pay->payment->currery;
        }



    }

    /**
     * 充值失败
     * @param string $exception 异常信息
     */
    public function rechargeFail(string $exception = '')
    {
        if ($exception) {
            $this->order->response = $exception;
        }
        $this->order->closed = 1;
        $this->order->state = Order::FAILED;
        $this->order->save();
    }



    /**
     * 提现记录同步响应,失败处理
     * @param array $param 接口入参
     * @param array $response
     * @param string $transaction_id
     * @param bool $state
     */
    public function withdrawalCallback(array $param, array $response, string $transaction_id, bool $state)
    {
        DB::transaction(function () use ($param, $response, $transaction_id, $state) {
            if (!$state) {
                Order::withdrawFail($this->order);
                $this->order->state = Order::FAILED;
                $this->order->closed = 1;  //关闭订单
                $this->order->remark = 'Re-withdraw after checking bank card details';  //默认备注
            }

            $this->order->param .= json_encode($param, JSON_UNESCAPED_UNICODE);
            $this->order->response .= json_encode($response, JSON_UNESCAPED_UNICODE);
            $this->order->payment_no = $transaction_id;
            $this->order->save();
        });
    }

    /**
     * 设置同步回跳地址
     */
    public function setReturnUrl(): void
    {
        if(empty($this->order->host)){
            $this->return_url = config('ThirdPay.redirect_url');
        }else{
            $this->return_url = 'https://' . $this->order->host;
        }

    }
}
