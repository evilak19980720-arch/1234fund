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
use Illuminate\Support\Facades\DB;
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
        $currery = isset($attach['currery']) ? $attach['currery']:'USDT-TRC';
        try {
            $this->scanTransactions($user_id, $currery);
            // 入账成功会清掉标记，查询随之停止
            if(Cache::get(sprintf(KeysHandler::USDTCHECK,$currery,$user_id)) == 1){
                Urecharge::dispatch($user_id , ['currery' => $currery])->delay(60)->onQueue(env('REDIS_QUEUE', 'default'));
            }
        }catch (\Exception $exception){
            info('TRC充值查询失败', ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
            // 只在查询窗口内重试，最多 5 次；之后由定时任务 recharge:scan 补查
            $retries = (int) ($attach['retries'] ?? 0);
            if($retries < 5 && Cache::get(sprintf(KeysHandler::USDTCHECK,$currery,$user_id)) == 1){
                Urecharge::dispatch($user_id ,['currery' => $currery, 'retries' => $retries + 1])->delay(120)->onQueue(env('REDIS_QUEUE', 'default'));
            }
        }
    }

    /**
     * 查询用户充值地址的链上转账并入账（不再派发队列任务，定时任务 recharge:scan 也直接调用）
     */
    public function scanTransactions($user_id, $currery){
        $address = self::getRechargeAddress($user_id,$currery);
        if(empty($address)){
            return;
        }
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
                    if($this->getTransactionSucess($user_id, $transaction_id, $amount, $actual_amount ,$currery, $tran)){
                        //入账成功才更新余额
                        CheckTrcBalance::dispatch($address)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                    }
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
                            if($this->getTransactionSucess($user_id, $transaction_id, $amount, $actual_amount ,$currery, $tran)){
                                //入账成功才更新余额
                                CheckTrcBalance::dispatch($address)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                            }
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
                    if($this->getTransactionSucess($user_id, $transaction_id, $amount, $actual_amount ,$currery, $tran)){
                        //入账成功才更新余额
                        CheckBscBalance::dispatch($address)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                    }
                }
            }
        }

    }


    //成功的转账记录
    public function getTransactionSucess($user_id, $transaction_id, $amount, $actual_amount ,$currery, $tran){
        $user_order = $this->creditDeposit($user_id, $transaction_id, $amount, $actual_amount, $currery, $tran);
        if(!$user_order){
            return false;
        }

        // 事务提交后再推送通知、触发充值事件
        //判断是否首冲
        Order::pushFirstMes($user_order);
        event(new UserRechargeEvent($user_order));

        Cache::forget(sprintf(KeysHandler::USDTCHECK,$currery,$user_id));

        return true;
    }

    /**
     * 入账（只写数据库，不推送通知、不触发事件）：返回入账的订单；已处理过、低于最低金额等未入账时返回 null
     * recharge:simulate 也调用它做模拟测试
     */
    public function creditDeposit($user_id, $transaction_id, $amount, $actual_amount, $currery, $tran): ?Order
    {
        $transaction_id = (string) $transaction_id;
        if($transaction_id === '' || $amount <= 0){
            return null;
        }
        $min = Configuration::getItem('recharge_min');
        $platform = $currery;

        // 整个入账在一个事务里完成；chain_deposits.tx_id 唯一，同一笔链上交易只有一个进程能插入成功，杜绝并发重复入账
        return DB::transaction(function () use ($user_id, $transaction_id, $amount, $actual_amount, $currery, $tran, $min, $platform) {
            $inserted = DB::table('chain_deposits')->insertOrIgnore([
                'tx_id' => strtolower($transaction_id),
                'currery' => $currery,
                'user_id' => $user_id,
                'amount' => $amount,
                'status' => 'credited',
                'created_at' => now(),
            ]);
            if(!$inserted){
                return null;
            }
            $deposit = DB::table('chain_deposits')->where('tx_id', strtolower($transaction_id));

            // 改造前已经入账的交易
            if(Order::where('payment_no','=',$transaction_id)->exists()){
                $deposit->update(['status' => 'existing']);
                return null;
            }

            // 低于最小金额不入账，但留一笔失败订单，方便用户和客服查到这笔转账
            if($amount < $min){
                $order = new Order();
                $order->platform = $platform;
                $order->payment_id = 0;
                $order->user_id = $user_id;
                $order->amount = $amount;
                $order->actual_amount = $actual_amount;
                $order->no = only_code();
                $order->type = Account::RECHARGE;
                $order->state = Order::FAILED;
                $order->closed = 1;
                $order->payment_no = $transaction_id;
                $order->remark = 'Below minimum deposit, not credited';
                $order->notify_response = json_encode($tran);
                $order->save();
                $deposit->update(['status' => 'below_min', 'order_id' => $order->id]);
                return null;
            }

            $user = User::where('id', '=', $user_id)->lockForUpdate()->first();
            if(!$user){
                // 回滚，chain_deposits 记录不保留
                throw new \RuntimeException('充值用户不存在：' . $user_id);
            }

            //查有没有最新一个充值订单,如果有则更新,没有则新建
            $user_order = Order::where('user_id','=',$user_id)
                ->where('type','=',Account::RECHARGE)
                ->where('state','=',0)
                ->where('amount','=',$amount)
                ->where('platform','=',$platform)
                ->orderByDesc('id')->lockForUpdate()->first();
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
            $deposit->update(['order_id' => $user_order->id]);

            return $user_order;
        });
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
