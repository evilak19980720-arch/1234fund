<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Lcobucci\JWT\Keys;

/**
 * 公告
 * @package App\Models
 */
class Notice extends Model
{
    use SoftDeletes;
    use DefaultDatetimeFormat;

    public static function getNotices($admin_user_id,$flag = "")
    {

        if ($flag == "top") {
            $notices = Cache::get(sprintf(KeysHandler::TOP_NOTICE,$admin_user_id));
        }elseif ($flag == "eject"){
            $notices = Cache::get(sprintf(KeysHandler::EJECT_NOTICE,$admin_user_id));
        }else {
            $notices = Cache::get(sprintf(KeysHandler::NOTICES,$admin_user_id));
        }

        if (!$notices || !count($notices)) {
            if ($flag == 'top') {
                $notices = self::query()->where('top', '=', '1')
                    ->where('admin_user_id','=',$admin_user_id)
                    ->orderBy('top', 'desc')->orderBy('created_at', 'desc')
                    ->select('id', 'title', 'content', 'eject', 'top', 'created_at')
                    ->limit(1)
                    ->get();
                Cache::put(sprintf(KeysHandler::TOP_NOTICE,$admin_user_id), $notices, KeysHandler::NOTICE_EXPIRE);
            }elseif ($flag == 'eject') {
                $notices = self::query()->where('eject', '=', '1')
                    ->where('admin_user_id','=',$admin_user_id)
                    ->orderBy('eject', 'desc')->orderBy('created_at', 'desc')
                    ->select('id', 'title', 'content', 'eject', 'top', 'created_at')
                    ->limit(1)
                    ->get();
                Cache::put(sprintf(KeysHandler::EJECT_NOTICE,$admin_user_id), $notices, KeysHandler::NOTICE_EXPIRE);
            } else {
                $notices = self::query()
                    ->where('admin_user_id','=',$admin_user_id)
                    ->orderBy('top', 'desc')->orderBy('created_at', 'desc')
                    ->select('id', 'title', 'content', 'top', 'created_at')
                    ->get();
                Cache::put(sprintf(KeysHandler::NOTICES,$admin_user_id), $notices, KeysHandler::NOTICE_EXPIRE);
            }
        }
        return $notices;
    }

    public static function getNotice($admin_user_id,$id)
    {
        $key = sprintf(KeysHandler::NOTICE, $id);
        $notice = Cache::get($key);
        if (!$notice) {
            $notice = self::query()->where('admin_user_id','=',$admin_user_id)->find($id);
            Cache::put($key, $notice, 60);
        }

        return $notice;
    }
}
