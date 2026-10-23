<?php

namespace App\Policies;

use App\Models\Friend;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FriendPolicy
{
    use HandlesAuthorization;

    public function delete(User $user,Friend $friend)
    {
        return $user->id == $friend->user_id;
    }
}
