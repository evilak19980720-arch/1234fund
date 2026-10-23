<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    use DefaultDatetimeFormat;

    //消息类型
    const DEFAULT_REPLAY = 0;   // 默认回复问题 ,文字展示
    const WITHDRAW = 1;  //提现相关问题
    const SERVERS = 2;  //联系客服
}
