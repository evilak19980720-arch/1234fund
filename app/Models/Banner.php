<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Banner extends Model
{
    protected function getUrlAttribute($value)
    {
        return $value ?: '';
    }

    public static function getBanner($type)
    {
        $key = sprintf(KeysHandler::BANNER,$type);
        $banners = Cache::get($key);
        if (!$banners) {
            $banners = self::where('type', '=', $type)->select('id', 'path', 'url')->orderBy('created_at', 'desc')->get();
            Cache::put($key, $banners, KeysHandler::BANNER_EXPIRE);
        }

        return $banners;
    }
}
