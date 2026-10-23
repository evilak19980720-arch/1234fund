<?php

namespace App\Http\Controllers\Api\v1;

use App\Handlers\KeysHandler;
use App\Http\Requests\Api\v1\TaskLogRequest;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\Type;
use App\Models\Vip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * 领取任务
 * @package App\Http\Controllers\Api\v1
 */
class TaskLogController extends Controller
{
    // 放弃任务
    public function update(TaskLogRequest $request)
    {
        $user = $request->user();
        $log = TaskLog::getTaskLog($request->id);

        if (!$log) {
            $this->errorResponse(Response::HTTP_OK, trans('error.20001'), 20001);
        }
        $this->authorize('update', $log);
        if ($log->state != TaskLog::CONDUCT) {
            $this->errorResponse(Response::HTTP_OK, trans('error.20011'), 20011);
        }

        DB::transaction(function () use ($log, $user) {
            // 改状态
            $log->state = TaskLog::GIVE_UP;
            $log->save();
            // 今日数量-1
            TaskLog::todayDecr($user->id, Type::getType(Redis::hget(sprintf(KeysHandler::TASK, $log->task_id), 'type_id'))->vip->id);
            // 任务数量-1
            Redis::hincrby(sprintf(KeysHandler::TASK, $log->task_id), 'finished', -1);
            Task::where('id', '=', $log->task_id)->decrement('finished', 1);
            // 清除我的任务缓存
            Cache::tags($user->id . 'mytasklist')->flush();
        });

        return $this->success();
    }
}
