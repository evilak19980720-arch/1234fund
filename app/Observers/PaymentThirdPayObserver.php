<?php

namespace App\Observers;

use App\Handlers\KeysHandler;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;

class PaymentThirdPayObserver
{
    public function saved(Payment $version)
    {
        Cache::forget(sprintf(KeysHandler::PAYMENT_THIRD_PAY_LIST,$version->currery));
    }

    public function deleted(Payment $version)
    {
        Cache::forget(sprintf(KeysHandler::PAYMENT_THIRD_PAY_LIST,$version->currery));
    }
}
