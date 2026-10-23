<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 投诉举报
 * @package App\Models
 */
class Complaint extends Model
{
    use DefaultDatetimeFormat;

    const CacheKey = 'complaint';

    protected $fillable = [
        'type','title','content','contact'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 回复
    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    public static function detail($id)
    {
        $key = sprintf('%s:%d',self::CacheKey,$id);
        if (!Cache::has($key)) {
            $data = self::with(['replay' => function($query) {
                $query->select('complaint_id','admin_user_id','content','created_at');
            }])->select('id','title','type','content','contact','created_at')->find($id);

            if ($data->replay) {
                // 获取管理员信息
                $admin = DB::table('admin_users')->select('name','avatar')->find($data->replay->admin_user_id);
                $data->replay->username = $admin->name;
                $data->replay->avatar = $admin->avatar ?: '';
            }

            Cache::put(self::CacheKey,$data,60);
        }
        return $data;
    }
}
