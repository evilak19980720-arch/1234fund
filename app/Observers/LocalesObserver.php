<?php

namespace App\Observers;

use App\Models\Locale;
use Illuminate\Support\Facades\Cache;

class LocalesObserver
{
    public function saved(Locale $locale)
    {
        Cache::forget(Locale::LocalesCache);
    }

    public function deleted(Locale $locale)
    {
        Cache::forget(Country::LocalesCache);
    }
}
