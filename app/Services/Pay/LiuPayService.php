<?php

namespace App\Services\Pay;

use App\Models\Bank;
use App\Models\Card;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Exception;

class LiuPayService extends PayService implements Pay
{
    /**
     * 充值通知地址
     * @var string
     */
    protected string $pay_notify_url = '/api/v1/liu_recharge';

    /**
     * 代付通知地址
     * @var string
     */
    protected string $withdrawal_notify_url = '/api/v1/liu_withdrawal';


    //充值
    public function recharge()
    {
        try {
            $this->setReturnUrl();
            $param = [
                'appId' => $this->merchant,
                'version' => '1.0',
                'amount' => "{$this->order->amount}",
                'customOrderId' => $this->order->no,
                'notifyUrl' => url($this->pay_notify_url),
                'callbackUrl' => $this->return_url,
                'payType' => $this->pay_code,
                'remark' => "{$this->order->user_id}",
                'responseType' => 'JSON',
                'ip' => request()->getClientIp(),
            ];

            $param['sign'] = $this->sign($param, $this->pay_key);
            $response = Http::asJson()->post($this->pay_url, $param);
            $result = json_decode($response->body(), true);
            $this->order->param = json_encode($param, JSON_UNESCAPED_UNICODE);
            $this->order->response = json_encode($result, JSON_UNESCAPED_UNICODE);
            if (isset($result['code']) && $result['code'] == '1') {
                //$this->order->payment_no = $result['data']['payUrl'];
                $this->order->save();
                return $result['data']['payUrl'];
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
        info('提现订单开始:'.$this->order->no);
        try {
            //$card = json_decode($this->order->card, true);
            $card = Card::query()->where('user_id', '=', $this->order->user_id)->first();
            if($card['bank_id'] == 0){
                $bank_code = 'GCASH';
            }else{
                $bank = Bank::getBank($card['bank_id']);
                $bank_code = $bank['uz_code'];
            }
            $param = [
                'appId' => $this->merchant,
                'version' => '1.0',
                'amount' => "{$this->order->actual_amount}",
                'username' => $card['username'],
                'phone' => $card['number'],
                'remark' => $this->order->no,
            ];
            info('提现订单返回:'.json_encode($param));
            $param['sign'] = $this->sign($param, $this->withdrawal_key);
            $param['withdrawalType'] = '2';
            $param['callback'] = url($this->withdrawal_notify_url);
            $response = Http::asJson()->post($this->withdrawal_url, $param);
            info('提现订单响应:'.$response);
            $result = json_decode($response->body(), true);
            $result = is_array($result) ? $result : [];
            //记录入参跟响应
            $state = isset($result['code']) && $result['code'] == 1;
            $this->withdrawalCallback($param, $result, $this->order->no, $state);
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
        //appid + version + amount + customOrderId + notifyUrl + callbackUrl + payType + remark + responseType + ip + appSecret
        //ksort($data);
        $str = '';
        foreach ($data as $key => $val) {
            if ($val != null) {
                $str .= $val;
            }
        }
        info($str  . $secret);
        return strtolower(md5($str  . $secret));
    }

    /**
     * 验签
     * @param array $params
     * @param string $type
     * @return bool
     */
    public function checkSign(array $params, string $type): bool
    {
        //appid + status + customOrderId + amount + realAmount + payTime + remark + appSecret
        $sign = $params['sign'];
        // 验证签名参数是否正确
        unset($params['sign']);
        $data = [$this->merchant,$params['status'],$params['customOrderId'],$params['amount'],$params['realAmount'],$params['payTime'],$params['remark']];
        return $sign == $this->sign($data, $this->pay_key);
    }

    public function checkSignWithdraw(array $params, string $type): bool
    {
        //appId+amount +remark+withdrawId
        $sign = $params['sign'];
        // 验证签名参数是否正确
        unset($params['sign']);
        $data = [$this->merchant,$params['amount'],$params['remark'],$params['withdrawId']];
        return $sign == $this->sign($data, $this->withdrawal_key);
    }




}
