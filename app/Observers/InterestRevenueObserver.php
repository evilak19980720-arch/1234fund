<?php

namespace App\Observers;

use App\Models\InterestRevenue;
use Illuminate\Support\Facades\Cache;

class InterestRevenueObserver
{
    public function saved(InterestRevenue $interest_revenue)
    {
        $tag = sprintf('%s_%d',InterestRevenue::CacheTag, $interest_revenue->user_id);
        Cache::tags($tag)->flush();  //刷新列表缓存
    }


}
