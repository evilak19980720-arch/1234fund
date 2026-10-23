<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class Phone extends Model
{
    use DefaultDatetimeFormat;
    public $timestamps = false;

    protected $fillable = [
        'phone', 'state', 'send_at'
    ];

}
