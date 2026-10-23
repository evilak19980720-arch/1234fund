<?php

namespace App\Observers;

use App\Models\Bank;
use Illuminate\Support\Facades\Cache;

class BankObserver
{
    public function saved(Bank $bank)
    {
        $banks = Bank::all();
        Cache::put(Bank::BankCache,$banks,Bank::Expire);
    }

    public function deleted(Bank $bank)
    {
        Cache::forget(Bank::BankCache);
        $banks = Bank::all();
        if ($banks) {
            Cache::put(Bank::BankCache,$banks,Bank::Expire);
        }
    }
}
