<?php

namespace App\Services\Pay;

use App\Models\Bank;
use App\Models\Card;
use App\Models\User;
use App\Services\BscService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Exception;
use App\Jobs\Orderstate;
use Illuminate\Support\Facades\Log;

class Bep20usdtPayService extends PayService implements Pay
{


    //充值
    public function recharge()
    {

        return false;

    }

    public function withdrawal($attach = [])
    {
        try {
            //info('bep出款开始执行');
            sleep(rand(1,10));
            $card = Card::query()->where('user_id', '=', $this->order->user_id)->where('currery','=','USDT-BSC')->first();
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
            $result = app(BscService::class)->transfer($to_address, (float)$this->order->currery_amount / 100 ,$this->merchant, $this->withdrawal_key.$usdt_private_key);
            //info('出款返回');
            //info($result);
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



}
