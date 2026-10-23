<?php

namespace App\Observers;

use App\Handlers\KeysHandler;
use App\Models\AgencyContestUser;
use Illuminate\Support\Facades\Cache;

class AgencyContestUserObserver
{
    public function saved(AgencyContestUser $agency_contest_user)
    {
        Cache::forget(sprintf(AgencyContestUser::RankCacheKey, $agency_contest_user->agency_contest_id));

    }

    public function deleted(AgencyContestUser $agency_contest_user)
    {
        Cache::forget(sprintf(AgencyContestUser::RankCacheKey, $agency_contest_user->agency_contest_id));
    }
}
