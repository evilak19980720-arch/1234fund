<?php

namespace App\Observers;

use App\Models\Vip;
use Illuminate\Support\Facades\Cache;

class VipObserver
{
    public function saved(Vip $vip)
    {
        Cache::forget(Vip::VipCache);
        $vips = Vip::getVip();
    }
}
