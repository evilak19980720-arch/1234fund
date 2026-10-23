<?php

namespace App\Observers;

use App\Handlers\KeysHandler;
use App\Models\Version;
use Illuminate\Support\Facades\Cache;

class VersionObserver
{
    public function saved(Version $version)
    {
        Cache::forget($version->type);
        Cache::forget(KeysHandler::VERSION_ALL);
    }

    public function deleted(Version $version)
    {
        Cache::forget($version->type);
        Cache::forget(KeysHandler::VERSION_ALL);
    }
}
