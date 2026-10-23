<?php

namespace App\Admin\Models;

use App\Models\Activity as ACT;
use App\Models\AdminUser;
use App\Models\Vip;

/**
 * 活动
 * @package App\Models
 */
class Activity extends ACT
{


    public function getAmountAttribute($value)
    {
        return $value/100;
    }



    public function adminUser()
    {
        return $this->belongsTo(AdminUser::class);
    }



    public function getVipsAttribute($value)
    {
        return explode(',', $value);
    }

    public function setVipsAttribute($value)
    {
        $this->attributes['vips'] = implode(',', $value);
    }

}
