<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Currency extends Model
{
    public $timestamps = false;

    const CurrenciesCache = 'currencies';
    const CurrenciesCacheWithdrawal = 'currenciesWithdrawal';
    const CurrenciesCacheAll = 'currenciesAll';
    const Expire = 3600 * 24 * 7;


    public static function getAllCurrencies($code = null){
        if (!Cache::has(self::CurrenciesCacheAll)) {
            $list = self::select('id','code','name','symbol','exchange_rate','recharge_type')->orderBy('sort')->orderBy('id')->get();
            //dump($list);
            Cache::put(self::CurrenciesCacheAll, $list, self::Expire);
        }
        $list = Cache::get(self::CurrenciesCacheAll);
        if(!empty($code)){
            $relist = [];
            foreach ($list as $val){
                $val->exchange_rate = floatval($val->exchange_rate);
                if(is_array($code) && in_array($val->code,$code)){
                    $relist[] =  $val;
                }elseif($code == $val->code){
                    return $val;
                }
            }
            return $relist;
        }else{
            return $list;
        }
    }

    public static function getCurrencies($code = null){
        if (!Cache::has(self::CurrenciesCache)) {
            $list = self::select('id','code','name','symbol','exchange_rate','recharge_type')->where('state','=',1)->orderBy('sort')->orderBy('id')->get();
            Cache::put(self::CurrenciesCache, $list, self::Expire);
        }
        $list = Cache::get(self::CurrenciesCache);
        if(!empty($code)){
            $relist = [];
            foreach ($list as $val){
                $val->exchange_rate = floatval($val->exchange_rate);
                if(is_array($code) && in_array($val->code,$code)){
                    $relist[] =  $val;
                }elseif($code == $val->code){
                   return $val;
                }
            }
            return $relist;
        }else{
            return $list;
        }
    }

    public static function getWithdrawalCurrencies($code = null){
        if (!Cache::has(self::CurrenciesCacheWithdrawal)) {
            $list = self::select('id','code','name','symbol','exchange_rate','recharge_type')->where('withdrawal_state','=',1)->orderBy('sort')->orderBy('id')->get();
            //dump($list);
            Cache::put(self::CurrenciesCacheWithdrawal, $list, self::Expire);
        }
        $list = Cache::get(self::CurrenciesCacheWithdrawal);
        if(!empty($code)){
            $relist = [];
            foreach ($list as $val){
                $val->exchange_rate = floatval($val->exchange_rate);
                if(is_array($code) && in_array($val->code,$code)){
                    $relist[] =  $val;
                }elseif($code == $val->code){
                    return $val;
                }
            }
            return $relist;
        }else{
            return $list;
        }
    }


}
