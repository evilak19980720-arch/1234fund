<?php

namespace App\Services\Pay;

use App\Models\Bank;
use App\Models\Card;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Exception;

class CloudPayService extends PayService implements Pay
{
    /**
     * 充值通知地址
     * @var string
     */
    protected string $pay_notify_url = '/api/v1/cloud_recharge';

    /**
     * 代付通知地址
     * @var string
     */
    protected string $withdrawal_notify_url = '/api/v1/cloud_withdrawal';


    //充值
    public function recharge()
    {
        try {
            $this->setReturnUrl();
            $param = [
                'merchant' => $this->merchant,
                'payment_type' => $this->pay_code,
                'amount' => $this->order->amount / 100,
                'order_id' => $this->order->no,
                'bank_code' => 'gcash',
                'callback_url' => url($this->pay_notify_url),
                'return_url' => $this->return_url,
            ];
            $param['sign'] = $this->sign($param, $this->pay_key);
            $response = Http::asForm()->post($this->pay_url, $param);
            $result = json_decode($response->body(), true);
            $this->order->param = json_encode($param, JSON_UNESCAPED_UNICODE);
            $this->order->response = json_encode($result, JSON_UNESCAPED_UNICODE);
            if (isset($result['message']) && $result['message'] == 'success' && isset($result['status']) && $result['status'] == 1) {
                //$this->order->payment_no = $result['orderNo'];
                $this->order->save();
                return $result['redirect_url'];
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
            //$card = json_decode($this->order->card, true);
            $card = Card::query()->where('user_id', '=', $this->order->user_id)->first();
            $param = [
                'merchant' => $this->merchant,
                'total_amount' => $this->order->actual_amount / 100,
                'callback_url' => url($this->withdrawal_notify_url),
                'order_id' => $this->order->no,
                'bank' => 'gcash',
                'bank_card_name' => $card['username'],
                'bank_card_account' => $card['number'],
                'bank_card_remark' => $card['number'],
            ];
            $param['sign'] = $this->sign($param, $this->withdrawal_key);
            $response = Http::asForm()->post($this->withdrawal_url, $param);
            $result = json_decode($response->body(), true);
            $result = is_array($result) ? $result : [];
            //记录入参跟响应
            if (isset($result['message']) && $result['message'] == 'success' && isset($result['status']) && $result['status'] == 1) {
                $state = true;
            }else{
                $state = false;
            }
            $this->withdrawalCallback($param, $result,  '', $state);
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
    protected function sign($data, $secret): string
    {
        ksort($data);
        $str = '';
        foreach ($data as $key => $val) {
            if ($val != null) {
                $str .= $key . '=' . $val . '&';
            }
        }
        return strtolower(md5($str . 'key=' . $secret));
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
        unset($params['sign'], $params['signType'], $params['merRetMsg']);
        return $sign == $this->sign($params, $type == 'pay' ? $this->pay_key : $this->withdrawal_key);
    }

}
