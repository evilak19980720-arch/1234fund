<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Country extends Model
{
    use DefaultDatetimeFormat;
    public $timestamps = false;

    const CountriesCache = 'countries';
    const Expire = 3600 * 24 * 7;

    /*
    public function getCurrenciesAttribute($value)
    {
        //return explode(',', $value);
    }
    */

    public function setCurreryAttribute($value)
    {
        $this->attributes['currery'] = implode(',', $value);
    }



    public static function getCountries($country_i = null){
        if (!Cache::has(self::CountriesCache)) {
            $list = self::select('id','country_i','country_name','area','lang','icon','currery','default_currery','open_reg','withdrawal_amount','first_withdrawal_free')->where('state','=',1)->orderBy('sort')->orderBy('id')->get();
            Cache::put(self::CountriesCache, $list, self::Expire);
        }
        $list = Cache::get(self::CountriesCache);

        if(!empty($country_i)){
            foreach ($list as $val){
                if($val->country_i == $country_i){
                    return  $val;
                }
            }
            return null;
        }else{
            return $list;
        }
    }


}
