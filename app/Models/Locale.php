<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Locale extends Model
{
    use DefaultDatetimeFormat;
    public $timestamps = false;

    const LocalesCache = 'locales';
    const Expire = 3600 * 24 * 7;

    public static function getLocales(){
        if (!Cache::has(self::LocalesCache)) {
            $list = self::select('id','code','viewname')->where('state','=',1)->orderBy('sort')->orderBy('id')->get();
            Cache::put(self::LocalesCache, $list, self::Expire);
        }
        $list = Cache::get(self::LocalesCache);
        return $list;

    }


    public static function getAdminLangs()
    {
        return Locale::where('state','=',1)->orderBy('sort')->orderBy('id')->pluck('name','code');
    }

}
