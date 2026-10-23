<?php

namespace App\Observers;

use App\Models\Card;
use Illuminate\Support\Facades\Cache;

class CardObserver
{
    public function saved(Card $card)
    {
        Cache::forget(sprintf('card:%d',$card->user_id));
    }

    public function deleted(Card $card)
    {
        Cache::forget(sprintf('card:%d',$card->user_id));
    }
}
