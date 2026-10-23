<?php

namespace App\Observers;

use App\Handlers\KeysHandler;
use App\Models\Task;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class TaskObserver
{
    public function deleted(Task $task)
    {
        // 删除详情
        $key = sprintf(KeysHandler::TASK, $task->id);
        Redis::del($key);
        // 删除分页
        Cache::tags('tasks')->flush();
    }
}
