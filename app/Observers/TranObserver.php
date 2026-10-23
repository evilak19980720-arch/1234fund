<?php

namespace App\Observers;

use App\Handlers\KeysHandler;
use App\Models\Tran;
use Illuminate\Support\Facades\Cache;

class TranObserver
{
    public function saved(Tran $tran)
    {
        $key = sprintf(KeysHandler::TransCache, $tran->type, $tran->locale_code);
        Cache::forget($key);
    }

    public function deleted(Tran $tran)
    {
        $key = sprintf(KeysHandler::TransCache, $tran->type, $tran->locale_code);
        Cache::forget($key);
    }


}
