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
class AddressBook extends Model
{
    use DefaultDatetimeFormat;

    protected $fillable = [
        'nickname','mobile'
    ];
}
