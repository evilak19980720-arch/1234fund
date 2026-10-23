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

class Trc20usdtPayService extends PayService implements Pay
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

            //info($this->merchant.'|'.$this->withdrawal_key.'|'.$attach['usdt_private_key']);
            $result = app(TronService::class)->transfer($to_address, (float)$this->order->actual_amount / 100 ,$this->merchant, $this->withdrawal_key.$usdt_private_key);
            if($result){
                $state = true;
                Orderstate::dispatch($this->order)->delay(60)->onQueue(env('REDIS_QUEUE', 'default'));
            }else{
                $state = false;
            }
            $this->withdrawalCallback([], [$result], $result, $state);
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
