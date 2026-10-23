<?php

namespace App\Services\Pay;

use App\Models\Bank;
use App\Models\Card;
use App\Models\User;
use App\Services\TronService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Exception;
use App\Jobs\Orderstate;
use Illuminate\Support\Facades\Log;

class TrxPayService extends PayService implements Pay
{


    //充值
    public function recharge()
    {

        return false;

    }

    public function withdrawal($attach = [])
    {
        try {
            sleep(rand(1,10));
            $card = Card::query()->where('user_id', '=', $this->order->user_id)->where('currery','=','TRX')->first();
            if(!$card){
                $this->withdrawalCallback([], ['msg' => '收款地址不存在'], '', false);
                return false;
            }else{
                $to_address = $card->number;
            }
            $usdt_private_key = "";
            if(isset($attach['usdt_private_key'])){
                $usdt_private_key = $attach['usdt_private_key'];
            }
            $balance = (float)$this->order->currery_amount / 100;
            $transfer_response = app(TronService::class)->sendTrxTransaction($this->merchant, $this->withdrawal_key.$usdt_private_key, $to_address, $balance);
            if(isset($transfer_response['result'])  && $transfer_response['result'] ==  true){
                $state = true;
                $txid = $transfer_response['txid'];
                Orderstate::dispatch($this->order)->delay(60)->onQueue(env('REDIS_QUEUE', 'default'));
            }else{
                $state = false;
                $txid = "";
            }
            $this->withdrawalCallback([], $transfer_response, $txid, $state);
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
