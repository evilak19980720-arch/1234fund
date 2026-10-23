<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Win extends Model
{
    use SoftDeletes, DefaultDatetimeFormat;


    const SHOW_FALSE = 0;//否
    const SHOW_TRUE = 1;//是
}
