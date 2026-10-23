<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

/**
 * 设备统计
 * @package App\Models
 */
class Statistics extends Model
{
    use DefaultDatetimeFormat;
    protected $fillable = [
        'type','device'
    ];
}
