<?php

namespace App\Observers;

use App\Handlers\KeysHandler;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;

class CustomerObserver
{
    public function saved(Customer $customer)
    {
        Cache::forget(KeysHandler::CUSTOMER);
    }

    public function deleted(Customer $customer)
    {
        Cache::forget(KeysHandler::CUSTOMER);
    }
}
