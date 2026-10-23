<?php

namespace App\Models;

use App\Jobs\UpdateUserStatisticJob;
use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class UserStatistic extends Model
{
    use DefaultDatetimeFormat;
    protected $fillable = ['user_id','phone','recharge','today_recharge','commission','one_level','two_level','three_level','register_time','subordinate_recharge','subordinate_today_recharge','subordinate_total'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store(User $user)
    {
        $user_st = UserStatistic::query()->where('user_id','=',$user->id)->first();
        if ($user_st) {
            UpdateUserStatisticJob::dispatch($user_st)->onQueue(env('REDIS_QUEUE', 'default'));
            return true;
        }
        //1.增加用户统计数据
        UserStatistic::query()->create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'register_time' => $user->created_at
        ]);
        //2.处理用户上级的邀请人数
        $user_ids = array_filter(explode(',', trim($user->relation, ',')));
        foreach ($user_ids as $user_id) {
            $one_level = $this->getYesterdayRegisterSubordinate($user_id,1);
            $two_level = $this->getYesterdayRegisterSubordinate($user_id,2);
            $three_level = $this->getYesterdayRegisterSubordinate($user_id,3);
            UserStatistic::query()->where('user_id','=',$user_id)->update([
                'one_level' => $one_level,
                'two_level' => $two_level,
                'three_level' => $three_level,
                'subordinate_total' => ($one_level + $two_level + $three_level),
                'updated_at' => Carbon::now()
            ]);
        }
    }
    //获取注册的下级人数
    protected function getYesterdayRegisterSubordinate(int $user_id,int $level): int
    {
        $condition = sprintf(',%d,%s$',$user_id, str_repeat('[0-9]+,', $level - 1));
        return User::query()->where('relation', 'regexp', $condition)->count();
    }
}
