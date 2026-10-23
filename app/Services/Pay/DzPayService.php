<?php

namespace App\Services\Pay;

use App\Models\Bank;
use App\Models\Card;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Exception;

class DzPayService extends PayService implements Pay
{
    /**
     * 充值通知地址
     * @var string
     */
    protected string $pay_notify_url = '/api/v1/dz_recharge';

    /**
     * 代付通知地址
     * @var string
     */
    protected string $withdrawal_notify_url = '/api/v1/dz_withdrawal';


    //充值
    public function recharge()
    {
        try {
            $this->setReturnUrl();
            $param = [
                'version' => '1.0',
                'mch_id' => $this->merchant,
                'notify_url' => url($this->pay_notify_url),
                //'page_url' => $this->return_url.'/rechargeSucess?no='.$this->order->no.'&amount='.$this->order->amount,
                //'page_url' => $this->return_url.'/loading?no='.$this->order->no,
                'mch_order_no' => $this->order->no,
                'pay_type' => $this->pay_code,
                'trade_amount' => $this->order->actual_amount / 100,
                'order_date' => Carbon::now()->toDateTimeString(),
                'goods_name' => 'recharge',
            ];
            $param['sign'] = $this->sign($param, $this->pay_key);
            $param['sign_type'] = 'MD5';
            $response = Http::asForm()->post($this->pay_url, $param);
            $result = json_decode($response->body(), true);
            $this->order->param = json_encode($param, JSON_UNESCAPED_UNICODE);
            $this->order->response = json_encode($result, JSON_UNESCAPED_UNICODE);
            if (isset($result['respCode']) && $result['respCode'] == 'SUCCESS' && isset($result['tradeResult']) && $result['tradeResult'] == '1') {
                $this->order->payment_no = $result['orderNo'];
                $this->order->save();
                return $result['payInfo'];
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
            //$card = Card::query()->where('user_id', '=', $this->order->user_id)->first();
            $card = json_decode($this->order->card, true);
            /*
            if($card['bank_id'] == 0){
                $bank_code = 'PIXPAY';
            }else{
                $bank = Bank::getBank($card['bank_id']);
                $bank_code = $bank['uz_code'];
            }
            */
            $currery = $this->currery;
            $acc_code = $card['ifsc'];
            switch ($currery){
                case "BDT":
                    $acc_code = $card['ifsc'];
                    if($acc_code == 'nagad'){
                        $acc_code = 'ngand';
                    }
                    break;
                case "INR":
                    $acc_code = 'IDPT0001';
                    break;


            }

            $param = [
                'mch_id' => $this->merchant,
                'mch_transferId' => $this->order->no,
                'transfer_amount' => $this->order->actual_amount / 100,
                'apply_date' => Carbon::now()->toDateTimeString(),
                'bank_code' => $acc_code,
                'receive_name' => $card['username'],
                'receive_account' => $card['number'],
                'back_url' => url($this->withdrawal_notify_url),
            ];

            switch ($currery){
                case "INR":
                    $param['remark'] = $card['ifsc'];
                    break;
            }

            $param['sign'] = $this->sign($param, $this->withdrawal_key);
            $param['sign_type'] = 'MD5';
            $response = Http::asForm()->post($this->withdrawal_url, $param);
            $result = json_decode($response->body(), true);
            $result = is_array($result) ? $result : [];
            //记录入参跟响应
            $state = isset($result['respCode']) && $result['respCode'] == 'SUCCESS';
            $this->withdrawalCallback($param, $result, $result['tradeNo'] ?? '', $state);
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
