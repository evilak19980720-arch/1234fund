<?php

namespace App\Observers;

use App\Models\InterestLog;
use Illuminate\Support\Facades\Cache;

class InterestLogObserver
{
    public function saved(InterestLog $interest_log)
    {
        $tag = sprintf('%s_%d',InterestLog::CacheTag, $interest_log->user_id);
        Cache::tags($tag)->flush();  //刷新列表缓存
    }


}
