<?php

namespace App\Models;

use App\Handlers\HtmlHandler;
use App\Handlers\KeysHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 手册
 * @package App\Models
 */
class Manual extends Model
{
    public $timestamps = false;

    public static function getList($page, $limit)
    {
        $key = sprintf(KeysHandler::MANUALS, $page, $limit);
        $manuals = Cache::get($key);
        if (!$manuals) {
            $manuals = self::orderBy('id', 'asc')->select('id','title')->offset($page)->limit($limit)->get();
            foreach ($manuals as &$item){
                $item->title = transtableadmin('manuals.title.'.$item->id);
            }

            Cache::tags(['manual'])->put($manuals, 60 * 60);
        }

        return $manuals;
    }

    public static function getManual($id)
    {
        $key = sprintf(KeysHandler::MANUAL, $id);
        $manual = Cache::get($key);
        if (!$manual) {
            $manual = self::find($id);
            Cache::put($key, $manual, 60 * 60);
        }

        $manual->title = transtableadmin('manuals.title.'.$manual->id);
        if(isset($manual['content'])){
            $manual['content'] = HtmlHandler::makeContent(transtableadmin('manuals.content.'.$manual->id.'.html'));
        }

        return $manual;
    }

    public static function clean($id=null)
    {
        if ($id) {
            Cache::forget(sprintf(KeysHandler::MANUAL,$id));
        } else {
            Cache::tags(['manual'])->flush();
        }
    }
}
