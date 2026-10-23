<?php

namespace App\Observers;
use App\Handlers\KeysHandler;
use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;

/**
 * Class ConfigurationObserver
 * @package App\Observers
 */
class ConfigurationObserver
{
    public function saved(Configuration $configuration)
    {
        Cache::forget(KeysHandler::CONFIGURATION);
    }

    public function deleted(Configuration $configuration)
    {
        Cache::forget(KeysHandler::CONFIGURATION);
    }
}
