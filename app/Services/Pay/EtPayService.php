<?php

namespace App\Services\Pay;

use App\Models\Bank;
use App\Models\Card;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Exception;

class EtPayService extends PayService implements Pay
{

    /**
     * 充值通知地址
     * @var string
     */
    protected string $pay_notify_url = '/api/v1/et_recharge';

    /**
     * 代付通知地址
     * @var string
     */
    protected string $withdrawal_notify_url = '/api/v1/et_withdrawal';


    //充值
    public function recharge()
    {
        try {
            $this->setReturnUrl();
            $order_amount = intval($this->order->actual_amount / 100);
            $param = [
                'mcid' => $this->merchant,
                'orderno' => $this->order->no,
                'price' => "$order_amount",
                'tdid' => $this->pay_code,
                'callback_url' => url($this->pay_notify_url),
                'returnUrl' => "#",
                //'returnUrl' => $this->return_url.'/rechargeSucess?no='.$this->order->no.'&amount='.$this->order->amount
            ];
            $param['sign'] = $this->sign($param, $this->pay_key);
            $response = Http::withoutVerifying()->asJson()->post($this->pay_url, $param);
            $result = json_decode($response->body(), true);
            $this->order->param = json_encode($param, JSON_UNESCAPED_UNICODE);
            $this->order->response = json_encode($result, JSON_UNESCAPED_UNICODE);
            if (isset($result['code']) && $result['code'] == 200) {
                $this->order->payment_no = $result['ordersn'];
                $this->order->save();
                return $result['data'];
            }
            $this->rechargeFail();
            return false;
        } catch (Exception $exception) {
            $error_message = json_encode(['msg' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()], JSON_UNESCAPED_UNICODE);
            $this->rechargeFail($error_message);
            return false;
        }
    }

    public function withdrawal()
    {
        try {
            $card = json_decode($this->order->card, true);
            $currery = $this->currery;
            if($currery != 'BDT'){
                $this->withdrawalCallback([], ['msg' => '货币不支持'], '', false);
                return;
            }

            $ifsc = $card['ifsc'];
            $tdid = 93;
            if($ifsc == 'bkash'){
                $tdid = 94;
            }
            //94 Bkash
            //93 NAGAD

            $actual_amount = intval($this->order->actual_amount / 100);
            $param = [
                'mcid' => $this->merchant,
                'orderno' => $this->order->no,
                'type' => 1,
                'price' => "$actual_amount",
                'tdid'  => $tdid,
                'zh' => $card['number'],
                'mc' => $card['username'],
                'bankName' => $ifsc,
                'bankCode' => $ifsc,
                'callback_url' => url($this->withdrawal_notify_url)
            ];

            $param['sign'] = $this->sign($param, $this->withdrawal_key);
            $response = Http::withoutVerifying()->asJson()->post($this->withdrawal_url, $param);
            $result = json_decode($response->body(), true);
            $result = is_array($result) ? $result : [];
            //记录入参跟响应
            if (isset($result['code']) && $result['code'] == 200) {
                $state = true;
                $transaction_id = $result['ordersn'];
            }else{
                $state = false;
                $transaction_id = '';
            }
            $this->withdrawalCallback($param, $result,  $transaction_id, $state);
            $this->order->save();
        } catch (Exception $exception) {
            $this->withdrawalCallback([], ['msg' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()], '', false);
        }
    }


    /**
     * 签名
     * @param $data
     * @param $secret
     * @return string
     */
    protected function sign($params, $key): string
    {
        ksort($params);
        $signStr = '';
        foreach ($params as $k => $v) {
            if ($v !== '' && $k !== 'sign') {
                $signStr .= $k . '=' . $v . '&';
            }
        }
        $signStr .= 'key=' . $key;
        return md5($signStr);
    }

    /**
     * 验签
     * @param array $params
     * @param string $type
     * @return bool
     */
    public function checkSign(array $params, string $type): bool
    {
        $sign = $params['sign'];
        // 验证签名参数是否正确
        unset($params['sign'], $params['signType'], $params['merRetMsg'], $params['ext_info']);
        return $sign == $this->sign($params, $type == 'pay' ? $this->pay_key : $this->withdrawal_key);
    }

}
