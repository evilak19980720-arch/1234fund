<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CollectTrctask extends Model
{
    use DefaultDatetimeFormat;

    protected $fillable = [
        'type','state','toaddress'
    ];

    const TYPE_ARR = [
        1 => 'USDT-TRC',
        2 => 'TRX',
        3 => 'USDT-BSC',
    ];
    const STATE_ARR = [
        0 => '未开始',
        5 => '正在转账',
        6 => '转账完成',
    ];


    public function collect_trctask_logs()
    {
        return $this->hasMany(CollectTrctaskLog::class);
    }

    protected static function booted()
    {
        static::deleting(function ($thismodel) {
            Db::table('collect_trctask_logs')->where('collect_trctask_id','=',$thismodel->id)->delete();
            return true;
        });
    }

    public static function overTask($id){
        $count = CollectTrctaskLog::query()
            ->where('collect_trctask_id','=',$id)
            ->whereIn('state',CollectTrctaskLog::FILE_STATE)
            ->count();
        if($count == 0){
            self::query()->where('id','=',$id)->update([
                'state' => 6
            ]);
        }

    }

}
