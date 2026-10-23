<?php


namespace App\Services\Pay;

use App\Models\Card;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PayPlusPayService extends PayService implements Pay
{
    /**
     * 充值通知地址
     * @var string
     */
    protected string $pay_notify_url = '/api/v1/pay_plus_recharge';


    /**
     * 代付通知地址
     * @var string
     */
    protected string $withdrawal_notify_url = '/api/v1/pay_plus_withdrawal';

    //充值
    public function recharge()
    {
        try {
            $this->setReturnUrl();
            $param = [
                'merId' => $this->merchant,
                'orderId' => $this->order->no,
                'orderAmt' => sprintf('%.2f', $this->order->amount / 100),
                'channel' => $this->pay_code,
                'desc' => 'recharge',
                'ip' => $_SERVER['SERVER_ADDR'],
                'notifyUrl' => url($this->pay_notify_url),
                'returnUrl' => $this->return_url,
                'nonceStr' => Str::random(32),
            ];
            $param['sign'] = $this->getSign($param, $this->pay_key);
            $response = Http::post($this->pay_url, $param);
            $result = json_decode($response->body(), true);
            $this->order->param = json_encode($param, JSON_UNESCAPED_UNICODE);
            $this->order->response = json_encode($result, JSON_UNESCAPED_UNICODE);
            if ($result['code'] == 1) {
                $this->order->payment_no = $result['data']['sysorderno'];
                $this->order->save();
                return $result['data']['payurl'];
            }
            $this->rechargeFail();
            return false;
        } catch (Exception $exception) {
            $error_message = json_encode(['msg' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()], JSON_UNESCAPED_UNICODE);
            $this->rechargeFail($error_message);
            return false;
        }
    }

    //提现
    public function withdrawal()
    {
        try {
            $card = Card::query()->where('user_id', '=', $this->order->user_id)->first();
            $param = [
                'merId' => $this->merchant,
                'orderId' => $this->order->no,
                'money' => sprintf('%.2f', $this->order->actual_amount / 100),
                'name' => $card->username,
                'ka' => $card->number,
                'bank' => 'ANDHRA BANK',
                'zhihang' => $card->ifsc,
                'notifyUrl' => url($this->withdrawal_notify_url),
                'nonceStr' => Str::random(32),
            ];
            $param['sign'] = $this->getSign($param, $this->withdrawal_key);
            $response = Http::post($this->withdrawal_url, $param);
            $result = json_decode($response->body(), true);
            $result = is_array($result) ? $result : [];
            //记录入参跟响应
            $this->withdrawalCallback($param, $result, '', isset($result['code']) && $result['code'] == 1);
            $this->order->save();
        } catch (Exception $exception) {
            $this->withdrawalCallback([], ['msg' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()], '', false);
        }
    }

    /**
     * 生成签名
     * @param array $param
     * @param string $key
     * @return string
     */
    protected function getSign(array $param, string $key): string
    {
        $str = $this->getSignStr($param, $key);
        $rsa = new Rsa($this->public_key, $this->private_key);
        return $rsa->sign($str);
    }


    protected function getSignStr(array $param, string $secret): string
    {
        ksort($param);
        $str = '';
        foreach ($param as $key => $val) {
            $str .= $key . '=' . $val . '&';
        }
        return strtoupper(md5($str . 'key=' . $secret));
    }


    /**
     * 验证签名
     * @param array $param
     * @param string $type
     * @return bool
     */
    public function checkSign(array $param, string $type): bool
    {
        $sign = $param['sign'];
        unset($param['sign']);
        $sign_str = $this->getSignStr($param, $type == 'pay' ? $this->pay_key : $this->withdrawal_key);
        $rsa = new Rsa($this->public_key, $this->private_key);
        return (bool)$rsa->verify($sign_str, $sign);
    }
}
