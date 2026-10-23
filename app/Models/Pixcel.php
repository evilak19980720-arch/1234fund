<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Pixcel extends Model
{

    public $timestamps = false;

    const PixcelsCache = 'pixcels';
    const Expire = 3600 * 24 * 7;


    public static function getPixcel($host = null){
        if (!Cache::has(self::PixcelsCache)) {
            $list = self::select('id','host','pixcel','tkpixcel')->orderBy('id')->get();
            Cache::put(self::PixcelsCache, $list, self::Expire);
        }
        $list = Cache::get(self::PixcelsCache);
        if(!empty($host)){
            foreach ($list as $val){
                if($val->host == $host){
                    return $val->pixcel;
                }
            }
            return "";
        }else{
            return "";
        }
    }

    public static function getTkpixcel($host = null){
        if (!Cache::has(self::PixcelsCache)) {
            $list = self::select('id','host','pixcel','tkpixcel')->orderBy('id')->get();
            Cache::put(self::PixcelsCache, $list, self::Expire);
        }
        $list = Cache::get(self::PixcelsCache);
        if(!empty($host)){
            foreach ($list as $val){
                if($val->host == $host){
                    return $val->tkpixcel;
                }
            }
            return "";
        }else{
            return "";
        }
    }

}
