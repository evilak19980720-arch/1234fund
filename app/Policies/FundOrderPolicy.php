<?php

namespace App\Policies;

use App\Models\FundOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundOrderPolicy
{
    use HandlesAuthorization;

    public function update(User $user, FundOrder $fundOrder)
    {
        return $fundOrder->user_id === $user->id && time() > Carbon::parse($fundOrder->fundPeriod->end_time)->timestamp && $fundOrder->status == FundOrder::UNTREATED && $fundOrder->win == FundOrder::WIN_YES;
    }
}
