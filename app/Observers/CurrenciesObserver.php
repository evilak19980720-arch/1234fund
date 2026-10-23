<?php

namespace App\Observers;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;

class CurrenciesObserver
{
    public function saved(Currency $currency)
    {
        Cache::forget(Currency::CurrenciesCache);
        Cache::forget(Currency::CurrenciesCacheWithdrawal);
        Cache::forget(Currency::CurrenciesCacheAll);
    }

    public function deleted(Currency $currency)
    {
        Cache::forget(Currency::CurrenciesCache);
        Cache::forget(Currency::CurrenciesCacheWithdrawal);
        Cache::forget(Currency::CurrenciesCacheAll);
    }
}
