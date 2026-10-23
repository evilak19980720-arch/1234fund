<?php

namespace App\Observers;

use App\Models\Rule;
use Illuminate\Support\Facades\Cache;

class RuleObserver
{
    public function saved(Rule $rule)
    {
        Cache::forget(Rule::CacheKey);
        Rule::getRule();
    }
}
