<?php

namespace App\Models;

use App\Events\UserRechargeEvent;
use App\Handlers\KeysHandler;
use App\Jobs\CheckTrcBalance;
use App\Jobs\CheckBscBalance;
use App\Jobs\Orderstate;
use App\Jobs\Urecharge;
use App\Models\Configuration;
use App\Services\BscService;
use App\Services\TRC20Service;
use App\Services\TronService;
use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class UserRechargeAddress extends Model
{
    use DefaultDatetimeFormat;
    public $timestamps = false;
    protected $fillable = ['user_id','address','private_key','balance', 'transferout_balance','trx_balance','transferout_trx_balance','block'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getRechargeAddress($user_id, $currery = "USDT-TRC"){
        $block = "TRC";
        if($currery == 'USDT-BSC'){
            $block = 'BSC';
        }
        $key = sprintf(KeysHandler::USERTRCADDRESS, $user_id, $block);
        $address =  Cache::get($key);
        if (!$address) {
            $data = self::where('user_id','=',$user_id)->where('block','=',$block)->first();
            if($data){
                $address =  $data->address;
            }else{
                switch ($block){
                    case 'TRC':
                        $result = app(TronService::class)->createAccount();
                        self::create([
                            'user_id' => $user_id,
                            'block' => $block,
                            'address' => $result->getAddress(true),
                            'private_key' => $result->getPrivateKey()
                        ]);
                        $address =  $result->getAddress(true);
                        break;
                    case 'BSC':
                        $result = app(BscService::class)->createAccount();
                        self::create([
                            'user_id' => $user_id,
                            'block' => $block,
                            'address' => $result['address'],
                            'private_key' => $result['key']
                        ]);
                        $address =  $result['address'];
                        break;
                    default:
                        $address = "";
                        break;
                }
            }
            Cache::put($key, $address, 60);
        }
        return $address;

    }

    /*
     *
  0 => array:7 [
    "transaction_id" => "5239dc0c7c68412fed39eae283c78ee29a0bf552bfa3317a0c66dbb9605dc1bc"
    "token_info" => array:4 [
      "symbol" => "USDT"
      "address" => "TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t"
      "decimals" => 6
      "name" => "Tether USD"
    ]
    "block_timestamp" => 1721719950000
    "from" => "TWQYaz3cHB6fykj8BmQnB8vfGT6RKJwoaf"
    "to" => "TWLvXMVAPwL8SgAAWsX91JVEFbUcwRXq8L"
    "type" => "Transfer"
    "value" => "1200000"
  ]
     * */
    public function checkRecharge($user_id ,$attach){
        try {
            //info($user_id);
            //查地址
            $currery = isset($attach['currery']) ? $attach['currery']:'USDT-TRC';
            $address = self::getRechargeAddress($user_id,$currery);
            //info($address);
            switch ($currery){
                case 'USDT-TRC':
                    $this->getUsdtTrcTransactions($user_id, $address, $currery);
                    break;
                case 'TRX':
                    $this->getTrxTransactions($user_id, $address, $currery);
                    break;
                case 'USDT-BSC':
                    $this->getUsdtBscTransactions($user_id, $address, $currery);
                    break;
            }
            if(Cache::get(sprintf(KeysHandler::USDTCHECK,$currery,$user_id)) == 1){
                Urecharge::dispatch($user_id , ['currery' => $currery])->delay(60)->onQueue(env('REDIS_QUEUE', 'default'));
            }
        }catch (\Exception $exception){
            info('TRC充值查询失败', ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
            Urecharge::dispatch($user_id ,['currery' => $currery])->delay(120)->onQueue(env('REDIS_QUEUE', 'default'));
        }
    }




    //查询USDT-TRC 交易
    public function getUsdtTrcTransactions($user_id, $address, $currery){
        //查交易记录
        $trans = app(TronService::class)->getTransactions($address, 10);
        if($trans['success'] == true){
            $trans_list = $trans['data'];
            //dump($trans_list);
            foreach ($trans_list as $tran){
                if(
                    $tran['to'] == $address
                    && $tran['type'] == 'Transfer'
                    && $tran['token_info']['symbol'] == 'USDT'
                    && $tran['token_info']['name'] == 'Tether USD'
                    && $tran['token_info']['address'] == 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'
                    //&& $tran['block_timestamp'] > ((time() - 30 * 60) * 1000)
                )
                {
                    $transaction_id = $tran['transaction_id'];
                    $amount = bcdiv($tran['value'],pow(10,$tran['token_info']['decimals']),2) * 100;
                    $actual_amount = $amount;
                    $this->getTransactionSucess($user_id, $transaction_id, $amount, $actual_amount ,$currery, $tran);
                    //更新余额
                    CheckTrcBalance::dispatch($address)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                }
            }
        }
    }

    //查询TRX 交易
    public function getTrxTransactions($user_id, $address, $currery){
        //查交易记录
        $trans = app(TronService::class)->getTrxTransactionsToAddress($address);
        if($trans['success'] == true){
            $trans_list = $trans['data'];
            //dump($trans_list);
            foreach ($trans_list as $tran){
                //$add = app(TronService::class)->getAddress('41a614f803b6fd780986a42c78ec9c7f77e6ded13c');
                //dump($add);
                if( 1
                    && isset($tran['ret'][0]['contractRet'])
                    && $tran['ret'][0]['contractRet'] == 'SUCCESS'
                    && isset($tran['raw_data']['contract'][0]['type'])
                    && $tran['raw_data']['contract'][0]['type']== 'TransferContract'
                    && isset($tran['raw_data']['contract'][0]['parameter']['value']['to_address'])
                    && !empty($tran['raw_data']['contract'][0]['parameter']['value']['to_address'])
                    && isset($tran['raw_data']['contract'][0]['parameter']['value']['amount'])
                    && !empty($tran['raw_data']['contract'][0]['parameter']['value']['amount'])
                ){
                    $to_address = $tran['raw_data']['contract'][0]['parameter']['value']['to_address'];
                    $to_address_arr = app(TronService::class)->getAddress($to_address);
                    $actual_amount = $tran['raw_data']['contract'][0]['parameter']['value']['amount'];
                    $actual_amount = bcdiv($actual_amount,pow(10,6),2);
                    //dump($actual_amount);
                    if($to_address_arr['base58'] == $address && $actual_amount > 3){
                        $transaction_id = $tran['txID'];
                        $curreryObj = Currency::getCurrencies($currery);
                        if(!empty($curreryObj)){
                            $actual_amount = $actual_amount * 100;
                            $amount = bcdiv($actual_amount, $curreryObj->exchange_rate);
                            $this->getTransactionSucess($user_id, $transaction_id, $amount, $actual_amount ,$currery, $tran);
                            //更新余额
                            CheckTrcBalance::dispatch($address)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                        }
                    }
                }
            }
        }

    }

    //查询USDT-Bsc 交易
    public function getUsdtBscTransactions($user_id, $address, $currery){
        //查交易记录
        $trans_list = app(BscService::class)->getTransactions($address, 10);
        if (is_array($trans_list)) {
            foreach ($trans_list as $tran){
                if(
                    strtolower($tran['to'] ?? '') == strtolower($address)
                    //&& $tran['tokenSymbol'] == 'BSC-USD'
                    && strtolower($tran['contractAddress'] ?? '') == '0x55d398326f99059ff775485246999027b3197955'
                )
                {
                    $transaction_id = $tran['hash'];
                    $amount = bcdiv($tran['value'],pow(10,$tran['tokenDecimal']),2) * 100;
                    $actual_amount = $amount;
                    $this->getTransactionSucess($user_id, $transaction_id, $amount, $actual_amount ,$currery, $tran);
                    //更新余额
                    CheckBscBalance::dispatch($address)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                }
            }
        }

    }


    //成功的转账记录
    public function getTransactionSucess($user_id, $transaction_id, $amount, $actual_amount ,$currery, $tran){
        $min = Configuration::getItem('recharge_min');
        if($amount >= $min){ //小于最小金额则不入账
            //查订单
            $order = Order::where('payment_no','=',$transaction_id)->first();
            $platform = $currery;
            if(!$order){

                //查有没有最新一个充值订单,如果有则更新,没有则新建
                $user_order = Order::where('user_id','=',$user_id)
                    ->where('type','=',Account::RECHARGE)
                    ->where('state','=',0)
                    ->where('amount','=',$amount)
                    ->where('platform','=',$platform)
                    ->orderByDesc('id')->first();
                if($user_order){
                    $user_order->amount = $amount;
                    $user_order->actual_amount = $actual_amount;
                    $user_order->paid_at = now();
                    $user_order->payment_no = $transaction_id;
                    $user_order->state = 1;
                    $user_order->closed = 1;
                    $user_order->platform = $platform;
                    $user_order->payment_id = 0;
                    $user_order->notify_response = json_encode($tran);
                    $user_order->save();
                }else{
                    // 生成本地订单
                    $user_order = new Order();
                    $user_order->platform = $platform;
                    $user_order->payment_id = 0;
                    $user_order->user_id = $user_id;
                    $user_order->amount = $amount;
                    $user_order->actual_amount = $actual_amount;
                    $user_order->no = only_code();
                    $user_order->type = Account::RECHARGE;
                    $user_order->state = 1;
                    $user_order->paid_at = now();
                    $user_order->closed = 1;
                    $user_order->payment_no = $transaction_id;
                    $user_order->notify_response = json_encode($tran);
                    $user_order->save();
                }


                $user = User::where('id', '=', $user_id)->first();
                // +钱
                $user->increment('balance', $amount);
                // 记账
                Account::create([
                    'order_id' => $user_order->id,
                    'type' => Account::RECHARGE,
                    'to' => $user_id,
                    'from' => 0,
                    'amount' => $amount,
                    'level' => 0
                ]);
                //判断是否首冲
                Order::pushFirstMes($user_order);
                event(new UserRechargeEvent($user_order));

                Cache::forget(sprintf(KeysHandler::USDTCHECK,$currery,$user_id));

                return true;
            }
        }

    }


    public static function trc20BalanceOf($address)
    {
        try {
            $trc20Wallet = new \App\Services\TronService();
            $balance = $trc20Wallet->balance($address);
            $trx_balance = $trc20Wallet->balanceTrx($address);
            //info($balance);
            self::where('address','=',$address)->update([
                'balance' => floor($balance * 100) /100,
                'trx_balance' => floor($trx_balance * 100) /100
            ]);
        }catch (\Exception $exception){
            info('TRC余额查询异常', ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
        }
    }

    public static function bsc20BalanceOf($address)
    {
        try {
            $Wallet = new \App\Services\BscService();
            $balance = $Wallet->balance($address);
            //$trx_balance = $Wallet->balanceTrx($address);
            //info($balance);
            self::where('address','=',$address)->update([
                'balance' => floor($balance * 100) /100
            ]);
        }catch (\Exception $exception){
            info('BSC余额查询异常', ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
        }
    }



}
