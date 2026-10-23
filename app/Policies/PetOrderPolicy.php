<?php

namespace App\Policies;

use App\Models\PetOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PetOrderPolicy
{
    use HandlesAuthorization;

    public function update(User $user, PetOrder $petOrder)
    {
        return $petOrder->user_id === $user->id;
    }
}
