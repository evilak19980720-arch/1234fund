<?php

namespace App\Jobs;


use App\Models\UserRechargeAddress;
use App\Services\BscService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use App\Services\TronService;
use App\Models\CollectTrctaskLog;
use App\Models\CollectTrctask;

/**
 * 归集队列
 * @package App\Jobs
 */
class CollectTrc implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $action;
    protected $attch;

    public function __construct($action, $attch)
    {
        $this->action = $action;
        $this->attch = $attch;
    }

    public function handle()
    {
        sleep(rand(1,2));
        switch ($this->action){
            case 'transfer':
                self::transfer();
                break;
            case 'checkTransfer':
                self::checkTransfer();
                break;

        }

    }

    public function transfer(){
        try {
            $collect_trctask_log_id = $this->attch['collect_trctask_log_id'];
            $collect_trctask_log = CollectTrctaskLog::query()->with('collect_trctask')->where('id','=',$collect_trctask_log_id)->first();
            if(!$collect_trctask_log){
                return false;
            }
            $trc20Wallet = new \App\Services\TronService();
            if($collect_trctask_log->collect_trctask->type == 1){
                //查询余额
                $balance = $trc20Wallet->balance($collect_trctask_log->address);
                UserRechargeAddress::query()->where('address','=',$collect_trctask_log->address)->update([
                    'balance' => floor($balance * 100) /100
                ]);
                if($balance > 0.5){
                    $collect_trctask_log->balance = floor($balance * 100) /100;
                    sleep(1);
                    $transaction_id = app(TronService::class)->transfer($collect_trctask_log->collect_trctask->toaddress, $balance ,$collect_trctask_log->address, $collect_trctask_log->private_key);
                    if($transaction_id){
                        $collect_trctask_log->transaction_id = $transaction_id;
                        $collect_trctask_log->start_at = now();
                        $collect_trctask_log->state = 6;
                        $collect_trctask_log->save();
                        CollectTrc::dispatch('checkTransfer',$collect_trctask_log)->delay(60)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                    }else{
                        $collect_trctask_log->state = 7;
                        $collect_trctask_log->save();
                        CollectTrctask::overtask($collect_trctask_log->collect_trctask->id);
                    }
                }else{
                    $collect_trctask_log->state = 10;
                    $collect_trctask_log->save();
                    CollectTrctask::overtask($collect_trctask_log->collect_trctask->id);
                }
            }elseif ($collect_trctask_log->collect_trctask->type == 2){
                //查询余额
                $trx_balance = $trc20Wallet->balanceTrx($collect_trctask_log->address);
                UserRechargeAddress::query()->where('address','=',$collect_trctask_log->address)->update([
                    'trx_balance' => floor($trx_balance * 100) /100
                ]);
                sleep(1);
                if($trx_balance > 2){
                    $collect_trctask_log->balance = floor($trx_balance * 100) /100;
                    //TRX转账
                    $balance = $trx_balance - 0;  //留0的手续费,消耗带宽不需要手续费
                    $transfer_response = app(TronService::class)->sendTrxTransaction($collect_trctask_log->address, $collect_trctask_log->private_key, $collect_trctask_log->collect_trctask->toaddress, $balance);
                    if(isset($transfer_response['result'])  && $transfer_response['result'] ==  true){
                        $collect_trctask_log->transaction_id = $transfer_response['txid'];
                        $collect_trctask_log->start_at = now();
                        $collect_trctask_log->state = 6;
                        $collect_trctask_log->save();
                        CollectTrc::dispatch('checkTransfer',$collect_trctask_log)->delay(60)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                    }else{
                        $collect_trctask_log->state = 7;
                        $collect_trctask_log->save();
                        CollectTrctask::overtask($collect_trctask_log->collect_trctask->id);
                    }
                }
                else{
                    $collect_trctask_log->state = 10;
                    $collect_trctask_log->save();
                    CollectTrctask::overtask($collect_trctask_log->collect_trctask->id);
                }
            }elseif($collect_trctask_log->collect_trctask->type == 3){  //BSC
                //查询余额
                $Wallet = new \App\Services\BscService();
                $balance = $Wallet->balance($collect_trctask_log->address);
                UserRechargeAddress::query()->where('address','=',$collect_trctask_log->address)->update([
                    'balance' => floor($balance * 100) /100
                ]);

                if($balance > 0.1){
                    $collect_trctask_log->balance = floor($balance * 100) /100;
                    sleep(1);
                    $transaction_id = $Wallet->transfer($collect_trctask_log->collect_trctask->toaddress, $balance ,$collect_trctask_log->address, $collect_trctask_log->private_key);
                    info($transaction_id);
                    if($transaction_id){
                        $collect_trctask_log->transaction_id = $transaction_id;
                        $collect_trctask_log->start_at = now();
                        $collect_trctask_log->state = 6;
                        $collect_trctask_log->save();
                        CollectTrc::dispatch('checkTransfer',$collect_trctask_log)->delay(60)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                    }else{
                        $collect_trctask_log->state = 7;
                        $collect_trctask_log->save();
                        CollectTrctask::overtask($collect_trctask_log->collect_trctask->id);
                    }
                }else{
                    $collect_trctask_log->state = 10;
                    $collect_trctask_log->save();
                    CollectTrctask::overtask($collect_trctask_log->collect_trctask->id);
                }
            }
        } catch (Exception $exception) {
            info('归集转账错误'.$this->attch['collect_trctask_log_id'], ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
        }
    }


    public function checkTransfer(){
        $collect_trctask_log = $this->attch;
        if(empty($collect_trctask_log->transaction_id)){
            return false;
        }
        if($collect_trctask_log->collect_trctask->type == 1)
        {
            $config = [
                'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',// USDT TRC20
                'decimals' => 6,
            ];
            try {
                $api = new \Tron\Api(new Client(['base_uri' => 'https://api.trongrid.io','verify' => false]));
                $trc20Wallet = new \Tron\TRC20($api, $config);
                $transactionReceipt = $trc20Wallet->transactionReceipt($collect_trctask_log->transaction_id);
                if($transactionReceipt->contractRet == 'SUCCESS' || $transactionReceipt->contractRet == 'FAIL' || $transactionReceipt->contractRet == 'REVERT' || $transactionReceipt->contractRet == 'OUT_OF_ENERGY'){
                    if($transactionReceipt->contractRet == 'SUCCESS'){
                        $collect_trctask_log->state = 8;
                        //更新地址金额
                        UserRechargeAddress::query()->where('address','=',$collect_trctask_log->address)->increment('transferout_balance',$collect_trctask_log->balance);
                        UserRechargeAddress::query()->where('address','=',$collect_trctask_log->address)->update([
                            'balance' => 0
                        ]);
                        CheckTrcBalance::dispatch($collect_trctask_log->address)->delay(60)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                    }else{
                        $collect_trctask_log->state = 9;
                        info('归集转账查询U结果失败:'.$collect_trctask_log->id, ['message' => $transactionReceipt]);
                    }
                    $collect_trctask_log->save();
                    CollectTrctask::overtask($collect_trctask_log->collect_trctask->id);
                }else{
                    info('归集转账查询U结果异常:'.$collect_trctask_log->id, ['message' => $transactionReceipt]);
                    CollectTrc::dispatch('checkTransfer',$collect_trctask_log)->delay(30)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                }
            }catch (\Exception $exception){
                info('归集转账查询U结果错误'.$collect_trctask_log->id, ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
                CollectTrc::dispatch('checkTransfer',$collect_trctask_log)->delay(60)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
            }
        }elseif ($collect_trctask_log->collect_trctask->type == 2){

            try {
                //info('开始查询trx');
                $transfer_response = app(TronService::class)->getTrxTransaction($collect_trctask_log->transaction_id);
                //info($transfer_response['ret']);
                if(isset($transfer_response['ret'][0]['contractRet']) &&
                    (
                        $transfer_response['ret'][0]['contractRet']  == 'SUCCESS' ||
                        $transfer_response['ret'][0]['contractRet']  == 'FAIL' ||
                        $transfer_response['ret'][0]['contractRet']  == 'REVERT' ||
                        $transfer_response['ret'][0]['contractRet']  == 'OUT_OF_ENERGY'
                    )
                ){
                    //info('222');
                    $contractRet = $transfer_response['ret'][0]['contractRet'];
                    if($contractRet == 'SUCCESS'){
                        //info('888');
                        $collect_trctask_log->state = 8;
                        //更新地址金额
                        UserRechargeAddress::query()->where('address','=',$collect_trctask_log->address)->increment('transferout_trx_balance',$collect_trctask_log->balance);
                        UserRechargeAddress::query()->where('address','=',$collect_trctask_log->address)->update([
                            'trx_balance' => 0
                        ]);
                        CheckTrcBalance::dispatch($collect_trctask_log->address)->delay(60)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                    }else{
                        $collect_trctask_log->state = 9;
                        info('归集转账查询TRX结果失败:'.$collect_trctask_log->id, ['message' => $transfer_response]);
                    }
                    $collect_trctask_log->save();
                    CollectTrctask::overtask($collect_trctask_log->collect_trctask->id);
                }else{
                    info('归集转账查询TRX结果异常:'.$collect_trctask_log->id, ['message' => $transfer_response]);
                    CollectTrc::dispatch('checkTransfer',$collect_trctask_log)->delay(30)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                }
            }catch (\Exception $exception){
                info('归集转账查询TRX结果错误'.$collect_trctask_log->id, ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
                CollectTrc::dispatch('checkTransfer',$collect_trctask_log)->delay(60)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
            }
        }
        elseif($collect_trctask_log->collect_trctask->type == 3)
        {

            try {
                //info($collect_trctask_log->transaction_id);
                $transactionReceipt = app(BscService::class)->transactionReceipt($collect_trctask_log->transaction_id);
                //info($transactionReceipt);
                if(empty($transactionReceipt))
                {
                    $collect_trctask_log->state = 9;
                    $collect_trctask_log->save();
                    CollectTrctask::overtask($collect_trctask_log->collect_trctask->id);
                    info('归集转账查询BSCU结果失败:'.$collect_trctask_log->id, ['message' => $transactionReceipt,'desc'=>'查询不到']);
                }elseif(isset($transactionReceipt['status'])
                    && ($transactionReceipt['status'] == '0x1' || $transactionReceipt['status'] == '0x0')
                    && $transactionReceipt['type'] == '0x0'
                    && $transactionReceipt['transactionHash'] == $collect_trctask_log->transaction_id
                )
                {
                    if($transactionReceipt['status'] == '0x1'){
                        $collect_trctask_log->state = 8;
                        //更新地址金额
                        UserRechargeAddress::query()->where('address','=',$collect_trctask_log->address)->increment('transferout_balance',$collect_trctask_log->balance);
                        UserRechargeAddress::query()->where('address','=',$collect_trctask_log->address)->update([
                            'balance' => 0
                        ]);
                        CheckBscBalance::dispatch($collect_trctask_log->address)->delay(60)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                    }else{
                        $collect_trctask_log->state = 9;
                        info('归集转账查询BSCU结果失败:'.$collect_trctask_log->id, ['message' => $transactionReceipt]);
                    }
                    $collect_trctask_log->save();
                    CollectTrctask::overtask($collect_trctask_log->collect_trctask->id);
                }else{
                    info('归集转账查询BSCU结果异常:'.$collect_trctask_log->id, ['message' => $transactionReceipt]);
                    CollectTrc::dispatch('checkTransfer',$collect_trctask_log)->delay(30)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                }
            }catch (\Exception $exception){
                info('归集转账查询BSCU结果错误'.$collect_trctask_log->id, ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
                CollectTrc::dispatch('checkTransfer',$collect_trctask_log)->delay(60)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
            }
        }

    }







}
