<?php

namespace App\Console\Commands;

use App\Jobs\GoogleTrans;
use App\Jobs\Orderstate;
use App\Jobs\UpdateUserStatisticJob;
use App\Jobs\CollectTrc;
use App\Models\Country;
use App\Models\Order;
use App\Models\User;
use App\Models\UserRechargeAddress;
use App\Models\UserStatistic;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use QL\QueryList;
use App\Services\TronService;
use App\Models\Tran;
use App\Models\CollectTrctaskLog;
use App\Models\CollectTrctask;

class Ceshi extends Command
{

    protected $signature = 'ceshi';

    protected $description = '测试';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        /*
        $trc20Wallet = new \App\Services\TronService();
        $balance = $trc20Wallet->balance('TWQYaz3cHB6fykj8BmQnB8vfGT6RKJwoaf');
        dump($balance);
        $balance2 = $trc20Wallet->balanceTrx('TWQYaz3cHB6fykj8BmQnB8vfGT6RKJwoaf');
        dump($balance2);
        */
        //UserRechargeAddress::trc20BalanceOf('TWQYaz3cHB6fykj8BmQnB8vfGT6RKJwoaf');




        //app(Tran::class)->tran(0,1,2,3);
        /*
        $result = app(TronService::class)->createAccount();
        dump($result);
        dump($result->getPrivateKey());
        dump($result->getAddress(true));
        */

        //app(UserRechargeAddress::class)->checkRecharge(140307);
        /*
                $users = User::whereIn('id',[186321,197044,197134])->get();
                foreach($users as $user){
                    (new UserStatistic())->store($user);
                }




                Redis::rpush("mailMessage:140307",json_encode([
                    "event"=>"rechargeSuccess", //firstRechargeSuccess
                    "amount"=> 65 * 100
                ]));
                Redis::rpush("mailMessage:140307",json_encode([
                    "event"=>"firstRechargeSuccess", //firstRechargeSuccess
                    "amount"=> 85 * 100
                ]));
                */
        /*

                $countries = Country::getCountries();
                foreach ($countries as &$item){
                    $key = 'country.'.$item->country_i;
                    GoogleTrans::dispatch(1, $key, $item->country_name, $item->country_name)->onQueue(env('REDIS_QUEUE', 'default'));
                }
        */

        /*
        UserStatistic::get()->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                UpdateUserStatisticJob::dispatch($user)->onQueue(env('REDIS_QUEUE', 'default'));
            }
        });
        */
        /*
                Order::query()->whereIn('id',[10398,10400,10413,10416,10417])->get()->orderBy('id')->chunk(100, function ($users) {
                    foreach ($users as $user) {
                        Orderstate::dispatch($user)->delay(60)->onQueue(env('REDIS_QUEUE', 'default'));
                    }
                });
        */

        //CollectTrc::dispatch('checkTransfer',CollectTrctaskLog::query()->with('collect_trctask')->where('id','=',214)->first())->onQueue(env('REDIS_QUEUE_LOW', 'default'));

        //$re = app(TronService::class)->validateAddress('');
        //dump($re);

        //保存会员上级
        /*
        User::query()->chunkById(100, function ($users) {
            foreach ($users as $user) {
                if (!empty($user->relation)) {
                    $user_ids = explode(',', trim($user->relation, ','));
                    $user_ids = array_reverse($user_ids);
                    $user->parent_id = $user_ids[0];
                    $user->save();
                }
            }
        });
        */
        User::query()->where('phone','like','88001%')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $this->info($user->phone);
            }
        });


    }
}
