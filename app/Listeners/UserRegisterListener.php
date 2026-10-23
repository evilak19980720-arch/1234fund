<?php

namespace App\Listeners;

use App\Events\UserRegisterEvent;
use App\Models\Configuration;
use App\Models\RegisterReward;
use App\Models\UserStatistic;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserRegisterListener implements ShouldQueue
{

    /**
     * 任务将被发送到的队列的名称
     * @var mixed
     */
    public $queue;


//    public User $user;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->queue = env('REDIS_QUEUE', 'default');
    }

    /**
     * Handle the event.
     *
     * @param UserRegisterEvent $event
     * @return void
     */
    public function handle(UserRegisterEvent $event)
    {

        (new UserStatistic())->store($event->user);
        //注册赠送不可用余额
        //app(RegisterReward::class)->store($event->user);


//        //1.增加用户统计数据
//        UserStatistic::query()->create([
//            'user_id' => $event->user->id,
//            'phone' => $event->user->phone,
//            'register_time' => $event->user->created_at
//        ]);
//        //2.处理用户上级的邀请人数
//        $user_ids = explode(',', trim($event->user->relation, ','));
//        foreach ($user_ids as $user_id) {
//            $one_level = $this->getYesterdayRegisterSubordinate($user_id,1);
//            $two_level = $this->getYesterdayRegisterSubordinate($user_id,2);
//            $three_level = $this->getYesterdayRegisterSubordinate($user_id,3);
//            UserStatistic::query()->where('user_id','=',$user_id)->update([
//                'one_level' => $one_level,
//                'two_level' => $two_level,
//                'three_level' => $three_level,
//                'subordinate_total' => ($one_level + $two_level + $three_level),
//                'updated_at' => Carbon::now()
//            ]);
//        }
    }

    //获取注册的下级人数
//    protected function getYesterdayRegisterSubordinate(int $user_id,int $level): int
//    {
//        $condition = sprintf(',%d,%s$',$user_id, str_repeat('[0-9]+,', $level - 1));
//        return User::query()->where('relation', 'regexp', $condition)->count();
//    }
}
