<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class CrowdsOrder extends Model
{
    use DefaultDatetimeFormat;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function crowd()
    {
        return $this->belongsTo(Crowd::class);
    }


}
