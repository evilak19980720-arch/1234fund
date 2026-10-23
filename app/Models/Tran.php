<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Google\Cloud\Translate\V2\TranslateClient;
use Exception;


class Tran extends Model
{
    use DefaultDatetimeFormat;
    public $timestamps = false;

    // 类型
    const TYPE = [
        0 => "客户端",
        1 => "服务端",
        2 => "后台",
    ];

    protected $fillable = [
        'locale_code', 'type', 'key', 'value', 'desc'
    ];


    public static function getTrans($type, $locale ,$key = null){
        $cachekey = sprintf(KeysHandler::TransCache, $type, $locale);
        if (!Cache::has($cachekey)) {
            $list = self::select('id','type','locale_code','key','value')->where('type','=',$type)->where('locale_code','=',$locale)->orderBy('id')->get();
            Cache::put($cachekey, $list, KeysHandler::TransCache_Expire);
        }
        $list = Cache::get($cachekey);
        if ($key && $list) {
            foreach ($list as $item) {
                if ($item->key == $key) {
                    return html_entity_decode($item->value, ENT_QUOTES);
                }
            }
        } else {
            if(!empty($list)){
                foreach ($list as &$val) {
                    $val->value = html_entity_decode($val->value, ENT_QUOTES);
                }
            }
            return $list;
        }

    }

    //翻译
    public function tran($type, $key, $value, $desc){
        $locales = Locale::query()->get();
        foreach ($locales as $locale){
            $insert_model = self::where('locale_code','=',$locale->code)->where('type','=',$type)->where('key','=',$key)->first();
            if(!$insert_model){
                $insert_model = new self();
            }
            $insert_model->locale_code = $locale->code;
            $insert_model->type  = $type;
            $insert_model->key = $key;
            $insert_model->value = self::getGoogleTran($value,$locale->code);
            $insert_model->desc = $desc;
            $insert_model->save();

            $cachekey = sprintf(KeysHandler::TransCache, $type, $locale->code);
            Cache::forget($cachekey);
        }
    }

    public function getGoogleTran($value, $target){
        try {
            sleep(3);
            $translate = new TranslateClient(['key' => config('app.google_translate_key')]);
            $result = $translate->translate($value, [
                'target' => $target
            ]);
            info($result);
            if (isset($result['text'])){
                //info(html_entity_decode($result['text'], ENT_QUOTES));
                return html_entity_decode($result['text'], ENT_QUOTES);
            }
            return $value;
        }catch (Exception $e){
            info('翻译失败:'.$target.'内容:'.$value);
        }

    }


}
