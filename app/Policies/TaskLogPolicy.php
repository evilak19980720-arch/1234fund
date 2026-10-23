<?php

namespace App\Policies;

use App\Models\TaskLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * 领取任务
 * @package App\Policies
 */
class TaskLogPolicy
{
    use HandlesAuthorization;

    public function update(User $user,TaskLog $log)
    {
        return $log->user_id == $user->id;
    }
}
