<?php

namespace App\Observers;

use App\Models\Interest;
use Illuminate\Support\Facades\Cache;

class InterestObserver
{
    public function saved(Interest $interest)
    {
        $key = sprintf('%s:%d',Interest::CacheKey, $interest->user_id);
        Cache::has($key) && Cache::forget($key);
    }


}
