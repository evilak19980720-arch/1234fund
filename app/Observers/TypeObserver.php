<?php

namespace App\Observers;

use App\Handlers\KeysHandler;
use App\Models\Type;
use Illuminate\Support\Facades\Cache;

class TypeObserver
{
    public function saved(Type $type)
    {
        Cache::forget(KeysHandler::TYPE);
        Type::getType();
    }
}
