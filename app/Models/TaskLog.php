<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * 任务记录
 * @package App\Models
 */
class TaskLog extends Model
{
    const CONDUCT = 0;  // 进行
    const EXAMINE = 1;  // 审核
    const SUCCESS = 2;  // 成功
    const FAIL = 3; // 失败
    const OVERTIME = -1; // 超时
    const GIVE_UP = -2; // 放弃

    use DefaultDatetimeFormat;

    protected $fillable = [
        'user_id', 'task_id', 'img', 'state'
    ];

    // 任务
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // 会员
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getTaskLog($id)
    {
        $key = sprintf(KeysHandler::TASK_LOG, $id);
        $log = Cache::get($key);
        if (!$log) {
            $log = self::find($id);
            $log && Cache::put($key, $log, 60);
        }

        return $log;
    }

    public static function finish($uid)
    {
        $key = sprintf(KeysHandler::FINISH_TASK, $uid);
        if (!Cache::has($key)) {
            $num = self::where('user_id', '=', $uid)->where('state', '=', self::SUCCESS)->count();
            Cache::put($key, $num, KeysHandler::FINISH_TASK_EXPIRE);
        }

        return (int)Cache::get($key);
    }

    /**
     * 获取用户今日完成数量
     * @param $uid
     * @param $vid
     * @return int
     */
    public static function todayCompleted($uid, $vid)
    {
        $key = sprintf(KeysHandler::TODAY_FINISH, $uid, $vid, Carbon::today()->format('Y-m-d'));

        if (!Cache::has($key)) {
            $num = self::where('user_id', '=', $uid)
                ->join('tasks', 'tasks.id', '=', 'task_logs.task_id')
                ->join('types', 'types.id', '=', 'tasks.type_id')
                ->where('vip_id', '=', $vid)
                ->where('task_logs.state', '<>', self::GIVE_UP)
                ->whereBetween('task_logs.created_at', [
                    Carbon::today()->startOfDay(),
                    Carbon::today()->endOfDay()
                ])
                ->count();
            Cache::put($key, $num, Carbon::tomorrow()->timestamp - Carbon::now()->timestamp);
        }

        return (int)Cache::get($key);
    }

    /**
     * 用户今日完成+1
     * @param $uid
     * @param $vid
     * @return bool|int
     */
    public static function todayIncr($uid, $vid)
    {
        $key = sprintf(KeysHandler::TODAY_FINISH, $uid, $vid, Carbon::today()->format('Y-m-d'));
        return Cache::increment($key, 1);
    }

    /**
     * 用户今日减1
     * @param $uid
     * @param $vid
     * @return bool|int
     */
    public static function todayDecr($uid, $vid)
    {
        $key = sprintf(KeysHandler::TODAY_FINISH, $uid, $vid, Carbon::today()->format('Y-m-d'));
        return Cache::decrement($key, 1);
    }

    /**
     * 是否完成过任务
     * @param $uid
     * @return bool
     */
    public static function didTask($uid)
    {
        $key = sprintf(KeysHandler::DID_TASK, $uid);

        $result = Cache::get($key);
        if (null === $result) {
            $result = self::where('user_id', '=', $uid)->where('state', '=', self::SUCCESS)->value('id');
            Cache::put($key, $result, 60);
        }

        return $result ? true : false;
    }

    /**
     * 计算收益
     * @param $user_id
     * @param $task_id
     */
    public function income($user_id, $task_id)
    {
        $task = (new Task())->getTask($task_id);

        // 自己的佣金
        User::where('id', '=', $user_id)->increment('balance', $task['price']);   // 余额
        User::where('id', '=', $user_id)->increment('revenue', $task['price']);   // 总收入
        // +明细
        Account::create([
            'type' => Account::COMMISSION,
            'to' => $user_id,
            'from' => 0,
            'amount' => $task['price'],
            'level' => 0
        ]);
        // 营销任务收入
        $userInfo = User::where('id', '=', $user_id)->select('id', 'vip_id', 'relation')->first();
        $vip = Vip::getVip($userInfo->vip_id);
        if ($vip->level > 0 && $userInfo->relation) {
            $user_ids = explode(',', trim($userInfo->relation, ','));
            $rules = Rule::getTaskRule();
            while (count($user_ids)) {
                $uid = array_pop($user_ids);
                $rule = array_shift($rules);
                $money = $task['price'] * ($rule['percent'] / 100);
                User::where('id', '=', $uid)->increment('balance', $money);
                User::where('id', '=', $uid)->increment('revenue', $money);
                Account::create([
                    'type' => Account::TIncome,
                    'to' => $uid,
                    'from' => $user_id,
                    'amount' => $money,
                    'level' => $rule['level']
                ]);
            }
        }
    }
}
