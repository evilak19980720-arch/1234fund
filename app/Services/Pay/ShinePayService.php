<?php

namespace App\Services\Pay;

use App\Models\Card;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

class ShinePayService extends PayService implements Pay
{
    //充值回调
    public string $notify_url = '/api/v1/shine_recharge';

    // 提现回调
    private string $withdrawal_notify_url = '/api/v1/shine_withdrawal';

    //充值
    public function recharge()
    {
        try {
            $this->setReturnUrl();
            $param = [
                'merchantId' => $this->merchant,
                'timestamp' => strtok(Carbon::now()->getPreciseTimestamp(3), '.'),
                'body' => [
                    'payTypeId' => '',  // 支付通道
                    'Amount' => $this->order->amount / 100,  // 金额
                    'OrderId' => $this->order->no,    // 订单编号
                    'Details' => 'recharge',    // 支付说明
                    'UserId' => (string)$this->order->user_id, // 用户标识
                    'notifyUrl' => url($this->notify_url),  // 异步通知地址
                    'redirectUrl' => $this->return_url,    // 同步跳转地址
                ]
            ];
            $res = $this->post($this->pay_url, $param, $this->pay_key);
            $this->order->param = json_encode($param, JSON_UNESCAPED_UNICODE);
            $this->order->response = json_encode($res, JSON_UNESCAPED_UNICODE);
            if (isset($res['status']) && $res['status'] == 0) {
                $this->order->payment_no = $res['body']['transactionId'];
                $this->order->save();
                return $res['body']['content'];
            }
            $this->rechargeFail();
            return false;
        } catch (Exception $exception) {
            $error_message = json_encode(['msg' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()], JSON_UNESCAPED_UNICODE);
            $this->rechargeFail($error_message);
            return false;
        }
    }


    //体现
    public function withdrawal()
    {
        try {
            $card = Card::query()->where('user_id', '=', $this->order->user_id)->first();
            $data = [
                'merchantId' => $this->merchant,
                'timestamp' => strtok(Carbon::now()->getPreciseTimestamp(3), '.'),
                'body' => [
                    'advPasswordMd5' => md5($this->password),
                    'orderId' => $this->order->no,    // 订单编号
                    'flag' => 0,
                    'bankCode' => $card->number,    // 卡号
                    'bankUser' => $card->username,    // 持卡人
                    'bankUserPhone' => $card->phone,    // 银行卡预留手机号
                    'bankUserEmail' => $card->email,    // 邮箱
                    'bankUserIFSC' => $card->ifsc,    // 银行卡持卡人IFSC码
                    'bankAddress' => 'aaa',    // 所在地区名称
                    'amount' => $this->order->actual_amount / 100,    // 金额
                    'realAmount' => $this->order->actual_amount / 100,    // 提现金额
                    'details' => 'withdraw',    // 提现说明
                    'notifyUrl' => url($this->withdrawal_notify_url),    // 回调地址
                ]
            ];
            $response = $this->post($this->withdrawal_url, $data, $this->withdrawal_key);
            $response = is_array($response) ? $response : [];
            $state = isset($response['status']) && $response['status'] == 0;
            $this->withdrawalCallback($data, $response, $rep['body']['platformOrderId'] ?? '', $state);
        } catch (Exception $exception) {
            $this->withdrawalCallback([], ['msg' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()], '', false);
        }
    }

    private function post($uri, $data, $secret)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                sprintf('%s: %s', 'Api-Sign', $this->sign($data, $secret)),
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    // 请求报文签名
    private function sign($data, $secret): string
    {
        return md5(sprintf('%s|%s', json_encode($data), $secret));
    }

    //验签
    public function checkSign($sign, $data, $type): bool
    {
        return $sign === md5(sprintf('%s|%s', json_encode($data), $type == 'pay' ? $this->pay_key : $this->withdrawal_key));
    }
}
