<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Version extends Model
{
    public function setApkAttribute($value)
    {
        $this->attributes['path'] = $value;
    }



    public static function getversion(){
        return Cache::rememberForever(KeysHandler::VERSION_ALL, function () {
            $version = self::all();
            //$h5 = $version->where('type', '=', 'h5')->sortByDesc('id')->first();
            $android = $version->where('type', '=', 'android')->sortByDesc('id')->first();
            //$ios = $version->where('type', '=', 'ios')->sortByDesc('id')->first();
            return [
                //'h5' => $h5->path ?? '',
                'android' => $android->path ?? '',
                //'ios' => $ios->path ?? '',
                //'download_text' => transtableadmin('configuration.download_text') ?? "" // Configuration::getItem('download_text')??""
            ];
        });
    }

}
