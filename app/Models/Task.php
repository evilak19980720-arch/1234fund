<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use App\Jobs\Overdue;
use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;


/**
 * 任务
 * @package App\Models
 */
class Task extends Model
{
    use DefaultDatetimeFormat;

    // 类型
    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    // 任务记录
    public function logs()
    {
        return $this->hasMany(TaskLog::class);
    }

    public function setPriceAttribute($value)
    {
        if (!$value) {
            $type = Type::getType($this->type_id);
            $vip = Vip::getVip($type->vip_id);
            $this->attributes['price'] = $vip->salary;
        } else {
            $this->attributes['price'] = $value;
        }
    }

    /**
     * 获取任务详情
     * @param $id
     * @return mixed
     */
    public function getTask($id)
    {
        $key = sprintf(KeysHandler::TASK, $id);

        $task = Redis::hgetall($key);
        if (!$task) {
            $task = $this->where('id', '=', $id)->first();
            if (!$task) {
                return null;
            }
            $seconds = Carbon::parse($task->end)->diffInSeconds(Carbon::now());

            // 修复：在 multi 之前获取 ttl，避免在事务中调用 ttl 命令
            $ttl = null;
            if ($seconds <= 0) {
                $ttl = Redis::ttl($key);
                $seconds = ($ttl > 0 ? $ttl : 60) + 10;  // 如果 ttl 无效，默认 60 秒
            }

            Redis::multi();
            Redis::hmset($key, $task->toArray());
            Redis::expire($key, $seconds);
            Redis::exec();
        }

        return $task;

    }

    // 领取任务
    public function receive($task, $user_id, $vip)
    {
        return DB::transaction(function () use ($task, $user_id, $vip) {
            $task = $this->where('id', '=', $task['id'])->lockForUpdate()->first();
            if($task['finished'] < $task['number']){
                // 记录
                $log = TaskLog::create([
                    'user_id' => $user_id,
                    'task_id' => $task['id']
                ]);

                // 批量 Redis 操作，减少网络往返
                $taskUsersKey = sprintf(KeysHandler::TASK_USERS, $task['id']);
                $taskKey = sprintf(KeysHandler::TASK, $task['id']);

                Redis::multi();
                Redis::zadd($taskUsersKey, $log->id, $user_id);
                Redis::hincrby($taskKey, 'finished', 1);
                Redis::exec();

                // 超时队列
                Overdue::dispatch($log)->delay(Carbon::now()->addMinutes($task['expire']));
                // 今日任务+1
                TaskLog::todayIncr($user_id, $vip->id);
                // 总领取+1
                $this->where('id', '=', $task['id'])->increment('finished', 1);
                return $log;
            }else{
                return  false;
            }
        });
    }

    /**
     * 判断是否领取过任务
     * @param $task  任务
     * @param $user_id  用户id
     * @return mixed
     */
    public static function isReceived($task, $user_id)
    {
        $key = sprintf(KeysHandler::TASK_USERS, $task['id']);

        if (!Redis::exists($key)) {
            $rows = TaskLog::where('task_id', '=', $task['id'])->select('id', 'user_id')->get();
            if ($rows && $rows->count() > 0) {
                $seconds = Carbon::parse($task['end'])->diffInSeconds(Carbon::now());
                $seconds = $seconds > 0 ? $seconds : 60;

                // 优化：批量构建 zadd 数据，减少循环中的 Redis 调用
                $zaddData = [];
                foreach ($rows as $row) {
                    $zaddData[$row->user_id] = $row->id;  // member => score
                }

                // 使用 multi 批量执行，比单独执行多个 zadd 更高效
                Redis::multi();
                Redis::zadd($key, $zaddData);
                Redis::expire($key, $seconds);
                Redis::exec();
            }
        }

        return Redis::zscore($key, $user_id);
    }

    public function setCache()
    {
        $key = sprintf(KeysHandler::TASK, $this->id);
        $seconds = Carbon::parse($this->end)->diffInSeconds(Carbon::now());

        Redis::multi();
        // 缓存任务
        Redis::hmset($key, [
            'id' => $this->id,
            'type_id' => $this->type_id,
            'title' => $this->title,
            'number' => $this->number,
            'finished' => $this->finished,
            'price' => $this->price,
            'content' => $this->content,
            'start' => $this->start,
            'end' => $this->end,
            'expire' => $this->expire,
            'automatic_audit' => $this->automatic_audit,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ]);
        Redis::expire($key, $seconds);

        // 创建任务领取
        $key = sprintf(KeysHandler::TASK_USERS, $this->id);
        Redis::zadd($key, 0, 0);
        Redis::expire($key, $seconds);
        Redis::exec();
    }
}
