<?php

namespace App\Observers;

use App\Models\Pixcel;
use Illuminate\Support\Facades\Cache;

class PixcelObserver
{
    public function saved(Pixcel $pixcel)
    {
        Cache::forget(Pixcel::PixcelsCache);
    }

    public function deleted(Pixcel $pixcel)
    {
        Cache::forget(Pixcel::PixcelsCache);
    }
}
