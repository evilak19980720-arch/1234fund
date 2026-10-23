<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\TronService;
use App\Services\BscService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Handlers\KeysHandler;

/**
 * 查询TRC交易订单状态
 * @package App\Jobs
 */
class Orderstate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle()
    {

            $this->check();

    }

    public function check(){
        $order = Order::query()->where('id',$this->order->id)->where('closed', '=', 0)->where('state', '=', 0)->first();
        if($order){

            if($order->currery == 'USDT-TRC'){
                $config = [
                    'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',// USDT TRC20
                    'decimals' => 6,
                ];
                try {
                    $api = new \Tron\Api(new Client(['base_uri' => 'https://api.trongrid.io','verify' => false]));
                    $trc20Wallet = new \Tron\TRC20($api, $config);
                    $transactionReceipt = $trc20Wallet->transactionReceipt($order->payment_no);
                    if($transactionReceipt->contractRet == 'SUCCESS' || $transactionReceipt->contractRet == 'FAIL' || $transactionReceipt->contractRet == 'REVERT' || $transactionReceipt->contractRet == 'OUT_OF_ENERGY'){
                        (new Order())->withdrawComplete([
                            'state' => $transactionReceipt->contractRet == 'SUCCESS' ? Order::SUCCESS : Order::FAILED,
                            'orderId' => $order->no,
                            'paidAt' => Carbon::now()->timestamp
                        ], json_decode(json_encode($transactionReceipt),true));
                    }else{
                        info('TRC转账交易查询异常', ['message' => $transactionReceipt]);
                        Orderstate::dispatch($order)->delay(15)->onQueue(env('REDIS_QUEUE', 'default'));
                    }
                }catch (\Exception $exception){
                    info('TRC转账交易查询失败', ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
                    Orderstate::dispatch($order)->delay(30)->onQueue(env('REDIS_QUEUE', 'default'));
                }
            }elseif ($order->currery == 'TRX'){
                try {
                    //info('开始查询trx');
                    $transfer_response = app(TronService::class)->getTrxTransaction($order->payment_no);
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
                            $re = true;
                        }else{
                            $re = false;
                        }
                        (new Order())->withdrawComplete([
                            'state' => $re ? Order::SUCCESS : Order::FAILED,
                            'orderId' => $order->no,
                            'paidAt' => Carbon::now()->timestamp
                        ], json_decode(json_encode($transfer_response),true));

                    }else{
                        info('归集转账查询TRX结果异常:'.$order->id, ['message' => $transfer_response]);
                        Orderstate::dispatch($order)->delay(15)->onQueue(env('REDIS_QUEUE', 'default'));
                    }
                }catch (\Exception $exception){
                    info('TRX转账交易查询失败'.$order->id, ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
                    Orderstate::dispatch($order)->delay(30)->onQueue(env('REDIS_QUEUE', 'default'));
                }
            }elseif($order->currery == 'USDT-BSC') {
                try {
                    $transactionReceipt = app(BscService::class)->transactionReceipt($order->payment_no);
                    //info($transactionReceipt);
                    /*
                     *成功
                     *array (
  'type' => '0x0',  - 表示交易类型的整数，0x0 表示传统交易，0x1 表示访问列表类型，0x2 表示动态费用。
  'from' => '0x312aca25ce7316340461176383c094acda772664',
  'to' => '0x55d398326f99059ff775485246999027b3197955',
  'status' => '0x1',  status: QUANTITY，1（成功）或 0（失败）
  'cumulativeGasUsed' => '0x1ad6cf',
  'logsBloom' => '0x00000000000000000000000000000000000000000000000000000000000000000000000000000000000800000000000000000000000000000000000000000000000000020000000001000008000000000000000000000000000000000000000000000000000000000000000000000000002000000000000000000010000000000000000000000000008000000000000000000000000000000000000000000000000000000002000000000000000000000001002000000000000000000000000000000002000000000000000000000000200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
  'logs' =>
  array (
    0 =>
    array (
      'address' => '0x55d398326f99059ff775485246999027b3197955',
      'topics' =>
      array (
        0 => '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef',
        1 => '0x000000000000000000000000312aca25ce7316340461176383c094acda772664',
        2 => '0x00000000000000000000000067e7cfe80d8876d94e3f9488752f8d4f9b09c2f8',
      ),
      'data' => '0x0000000000000000000000000000000000000000000000000f43fc2c04ee0059',
      'blockNumber' => '0x3441c4e',
      'transactionHash' => '0x90c21beb43206149e15f10c3a803aceebdab229c5070885a99d03175ffd135e8',
      'transactionIndex' => '0x7',
      'blockHash' => '0x35b81f16a18cd4de506962b267e212ea337cf60157df5c5cf8c8acfe07800fa3',
      'logIndex' => '0x29',
      'removed' => false,
    ),
  ),
  'transactionHash' => '0x90c21beb43206149e15f10c3a803aceebdab229c5070885a99d03175ffd135e8',
  'contractAddress' => NULL,
  'gasUsed' => '0xc99f',
  'blockHash' => '0x35b81f16a18cd4de506962b267e212ea337cf60157df5c5cf8c8acfe07800fa3',
  'blockNumber' => '0x3441c4e',
  'transactionIndex' => '0x7',
  'effectiveGasPrice' => '0x0',
)
                     *
                     *失败
                     array (
  'type' => '0x0',
  'from' => '0x84430af0f48742599062e40a9831d87161654296',
  'to' => '0x55d398326f99059ff775485246999027b3197955',
  'status' => '0x0',
  'cumulativeGasUsed' => '0x19bbea',
  'logsBloom' => '0x00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
  'logs' =>
  array (
  ),
  'transactionHash' => '0xb7020f9cdc990c8360773ec7e82439ff8f2014377e5374fdf65dc6be7cddcacb',
  'contractAddress' => NULL,
  'gasUsed' => '0x60e4',
  'blockHash' => '0x0e64beedb713c9bdd708744bf5acdf4860dcefa558b499928609ce62af349e77',
  'blockNumber' => '0x3441b26',
  'transactionIndex' => '0xb',
  'effectiveGasPrice' => '0x0',
)

                    查不到
                    false
                     * */

                    if (
                        isset($transactionReceipt['status'])
                        && ($transactionReceipt['status'] == '0x1' || $transactionReceipt['status'] == '0x0')
                        && $transactionReceipt['type'] == '0x0'
                        && $transactionReceipt['transactionHash'] == $order->payment_no
                    ) {
                        (new Order())->withdrawComplete([
                            'state' => $transactionReceipt['status'] == '0x1' ? Order::SUCCESS : Order::FAILED,
                            'orderId' => $order->no,
                            'paidAt' => Carbon::now()->timestamp
                        ], json_decode(json_encode($transactionReceipt), true));
                    } else {
                        info('BSC转账交易查询异常', ['message' => $transactionReceipt]);
                        Orderstate::dispatch($order)->delay(30)->onQueue(env('REDIS_QUEUE', 'default'));
                    }
                } catch (\Exception $exception) {
                    info('BSC转账交易查询失败', ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
                    Orderstate::dispatch($order)->delay(60)->onQueue(env('REDIS_QUEUE', 'default'));
                }
            }

        }
    }

}
