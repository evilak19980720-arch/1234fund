<?php

namespace App\Observers;

use App\Handlers\KeysHandler;
use App\Models\PaymentThirdCode;
use Illuminate\Support\Facades\Cache;

class PaymentThirdCodeObserver
{
    public function saved(PaymentThirdCode $version)
    {
        Cache::forget(sprintf(KeysHandler::PAYMENT_THIRD_PAY_LIST,$version->payment->currery));
    }

    public function deleted(PaymentThirdCode $version)
    {
        Cache::forget(sprintf(KeysHandler::PAYMENT_THIRD_PAY_LIST,$version->payment->currery));
    }
}
