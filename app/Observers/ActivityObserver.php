<?php

namespace App\Observers;

use App\Handlers\KeysHandler;
use App\Models\Activity;
use Illuminate\Support\Facades\Cache;

class ActivityObserver
{
    public function saved(Activity $activity)
    {
        Cache::forget(KeysHandler::ACTIVITIES);
        Cache::forget(sprintf(KeysHandler::ACTIVITY,$activity->id));
    }

    public function deleted(Activity $activity)
    {
        Cache::forget(KeysHandler::ACTIVITIES);
        Cache::forget(sprintf(KeysHandler::ACTIVITY,$activity->id));
    }
}
