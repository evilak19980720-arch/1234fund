<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class CollectTrctaskLog extends Model
{
    use DefaultDatetimeFormat;
    public $timestamps = false;
    protected $fillable = [
        'collect_trctask_id','address_id','address','private_key','state','transaction_id','start_at','balance'
    ];

    const STATE_ARR = [
        0 => '未开始',
        5 => '等待执行转账队列',
        6 => '已转账等待查询结果',
        7 => '转账失败',
        8 => '转账成功',
        9 => '转账结果失败',
        10 => '余额不足失败',
    ];

    const FILE_STATE = [0,5,6];

    public function collect_trctask()
    {
        return $this->belongsTo(CollectTrctask::class);
    }


}
