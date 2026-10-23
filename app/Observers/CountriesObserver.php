<?php

namespace App\Observers;

use App\Models\Country;
use Illuminate\Support\Facades\Cache;

class CountriesObserver
{
    public function saved(Country $country)
    {
        Cache::forget(Country::CountriesCache);
    }

    public function deleted(Country $country)
    {
        Cache::forget(Country::CountriesCache);
    }
}
