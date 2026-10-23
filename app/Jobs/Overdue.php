<?php

namespace App\Jobs;

use App\Handlers\KeysHandler;
use App\Models\Task;
use App\Models\TaskLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * 逾期
 * @package App\Jobs
 */
class Overdue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $log;

    public function __construct(TaskLog $log)
    {
        $this->log = $log;
    }

    public function handle()
    {
        DB::transaction(function () {
            $log = DB::table('task_logs')->where('id','=',$this->log->id)->sharedLock()->first();
            if ($log->state == TaskLog::CONDUCT) {
                // 状态->超时
                DB::table('task_logs')->where('id','=',$this->log->id)->update([
                    'state' => TaskLog::OVERTIME
                ]);
                // 任务领取数量-1
                Redis::hincrby(sprintf(KeysHandler::TASK,$this->log->task_id),'finished',-1);
                DB::table('tasks')->where('id','=',$this->log->task_id)->decrement('finished',1);
                Cache::forget(sprintf(KeysHandler::TASK_LOG,$log->id));
            }
        });
    }
}
