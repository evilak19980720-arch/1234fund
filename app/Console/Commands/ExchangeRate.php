<?php

namespace App\Console\Commands;

use App\Jobs\GoogleTrans;
use App\Jobs\Orderstate;
use App\Jobs\UpdateUserStatisticJob;
use App\Jobs\CollectTrc;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Order;
use App\Models\User;
use App\Models\UserRechargeAddress;
use App\Models\UserStatistic;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use QL\QueryList;
use App\Services\TronService;
use App\Models\Tran;
use App\Models\CollectTrctaskLog;
use App\Models\CollectTrctask;

class ExchangeRate extends Command
{

    protected $signature = 'updateExchangeRate';

    protected $description = '自动更新货币汇率';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        /*
                    //获取所有交易对
                    $response = Http::withOptions([
                        'verify' => false,
                    ])->get($url . "/v2/settings/common/symbols");
                    $result = json_decode($response->body(), true);
                    if ($result['status'] == 'ok') {
                        $data = $result['data'];
                        info($data);
                    }
        */

        $exchange_rate = 0;
        try {
            $response = Http::withOptions([
                'verify' => false,
            ])->get("https://api.huobi.pro/market/detail/merged" , ['symbol' => "trxusdt"]);
            $result = json_decode($response->body(), true);
            if ($result['status'] == 'ok') {
                $data = $result['tick'];
                //info($param);
                //info($data);
                $exchange_rate = bcdiv(1, $data['close'] , 4);
                $currency_trc = Currency::query()->where('code',"TRX")->first();
                $currency_trc->exchange_rate = $exchange_rate;
                $currency_trc->save();

            }
        } catch (\Exception $exception) {
            info('火币查询错误'.$exception->getFile().$exception->getMessage().$exception->getLine());
        }

        if($exchange_rate === 0){
            return ;
        }

        //info($exchange_rate);

        $exchange_rate_api = $this->check();
        if(empty($exchange_rate_api)){
            return ;
        }
        //info($exchange_rate_api);
        $currencies = Currency::query()->whereNotIn('code', ["TRX","USDT-TRC","USDT-BSC"])->get();
        foreach ($currencies as $currency){
            if (isset($exchange_rate_api[$currency->code])){
                $currency->exchange_rate = bcmul($exchange_rate_api[$currency->code] , 1, $this->getDecimalPlaces($exchange_rate_api[$currency->code]));

                info($currency->exchange_rate);
                $currency->save();
            }
        }

    }

    /*
    https://www.nowapi.com/
    https://sapi.k780.com/?app=finance.rate&scur=USD&tcur=CNY&appkey=APPKEY&sign=SIGN
    AppKey / Sign 配置在 .env 的 K780_APPKEY / K780_SIGN
     * */
    public function check(){
        $exchange_rate_api = [];
        try {
            $param = [
                'scur'   => 'USD',  //原币种编号
                'tcur'   => 'egp,inr,vnd,cop,ars,bdt,php',  //目标币种编号,多个用逗号隔开
                'appkey' => config('services.k780.appkey'),
                'sign'   => config('services.k780.sign'),
                'format' => 'json',
                'app'    => 'finance.rate'
            ];
            $response = Http::withOptions([
                'verify' => false,
            ])->post("https://sapi.k780.com/" , $param);
            $result = json_decode($response->body(), true);
            if ($result['success'] == 1) {
                if (isset($result['result']['lists'])){
                    $data = $result['result']['lists'];
                    foreach ($data as $v){
                        $exchange_rate_api[$v['tcur']] = $v['rate'];
                    }
                }
            }
        } catch (\Exception $exception) {
            info('汇率查询失败:'.$exception->getFile().$exception->getMessage().$exception->getLine());
        }
        return $exchange_rate_api;
    }

    public function getDecimalPlaces($number) {
        if (preg_match('/\.(\d+)/', (string)$number, $matches)) {
            return strlen($matches[1]);
        }
        return 0;
    }


}
