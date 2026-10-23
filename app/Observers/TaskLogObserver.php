<?php

namespace App\Observers;

use App\Handlers\KeysHandler;
use App\Models\Account;
use App\Models\Rule;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;
use App\Models\Vip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TaskLogObserver
{
    public function saved(TaskLog $log)
    {
        Cache::forget(sprintf(KeysHandler::TASK_LOG,$log->id));
    }
}
