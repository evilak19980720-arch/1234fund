<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

/**
 * 记录
 * @package App\Models
 */
class Record extends Model
{
    use DefaultDatetimeFormat;
    protected $fillable = [
        'type','device','number'
    ];
}
