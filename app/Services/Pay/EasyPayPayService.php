<?php

namespace App\Services\Pay;

use App\Models\Card;
use Exception;
use Illuminate\Support\Facades\Http;

class EasyPayPayService extends PayService implements Pay
{
    /**
     * 充值通知地址(联系平台人员配置)
     * @var string
     */
    protected string $pay_notify_url = '/api/v1/easy_pay_recharge';

    /**
     * 代付通知地址 (联系平台人员配置)
     * @var string
     */
    protected string $withdrawal_notify_url = '/api/v1/easy_pay_withdrawal';

    //充值
    public function recharge()
    {
        try {
            $param = [
                'merchantId' => $this->merchant,
                'orderId' => $this->order->no,
                'coin' => $this->order->amount / 100,
                'productId' => $this->pay_code,
                'goods' => 'recharge',
                'attach' => $this->order->no
            ];
            $param['sign'] = $this->getSign($param, $this->pay_key);
            $response = Http::withHeaders($this->header($param))->post($this->pay_url, $param);
            $result = json_decode($response->body(), true);
            $this->order->param = json_encode($param, JSON_UNESCAPED_UNICODE);
            $this->order->response = json_encode($result, JSON_UNESCAPED_UNICODE);
            if (isset($result['code']) && $result['code'] == 0) {
                $this->order->save();
                return $result['data']['url'];
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
            $card = Card::query()->where('user_id', '=', $this->order->user_id)->first();
            $param = [
                'merchantId' => $this->merchant,
                'outTradeNo' => $this->order->no,
                'coin' => $this->order->actual_amount / 100,
                'bankName' => 'ANDHRA BANK',
                'city' => 'Mumbai',
                'ifscCode' => $card->ifsc,
                'province' => 'Mumbai',
                'bankBranchName' => 'Yes Bank',
                'bankAccountName' => $card->username,
                'bankCardNum' => $card->number,
                'transferCategory' => 'IMPS'
            ];
            $param['sign'] = $this->getSign($param, $this->withdrawal_key);
            $response = Http::withHeaders($this->header($param))->post($this->withdrawal_url, $param);
            $result = json_decode($response->body(), true);
            $result = is_array($result) ? $result : [];
            //记录入参跟响应
            $state = isset($result['code']) && $result['code'] == 0;
            $this->withdrawalCallback($param, $result, '', $state);
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
    protected function getSign($data, $secret): string
    {
        ksort($data);
        $str = '';
        foreach ($data as $key => $val) {
            $str .= $key . '=' . $val . '&';
        }
        return strtoupper(md5($str . 'key=' . $secret));
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
        unset($params['sign'], $params['attach'], $params['time']);
        return $sign == $this->getSign($params, $type == 'pay' ? $this->pay_key : $this->withdrawal_key);
    }


    /**
     * 处理header头信息
     * @param array $param
     * @return string[]
     */
    protected function header(array $param): array
    {
        return [
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen(json_encode($param)),
        ];
    }
}
