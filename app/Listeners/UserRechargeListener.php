<?php

namespace App\Listeners;

use App\Events\UserRechargeEvent;
use App\Models\User;
use App\Models\UserStatistic;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class UserRechargeListener implements ShouldQueue
{

    /**
     * 任务将被发送到的队列的名称
     * @var mixed
     */
    public $queue;

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
     * @param  UserRechargeEvent  $event
     * @return void
     */
    public function handle(UserRechargeEvent $event)
    {
        $user = User::query()->where('id','=',$event->order->user_id)->first();
        (new UserStatistic())->store($user);
        //1处理自己充值金额，今日充值金额
        UserStatistic::query()->where('user_id','=',$event->order->user_id)->update([
            'recharge' => DB::raw('`recharge` + '.$event->order->amount),
            'today_recharge' => DB::raw('`today_recharge` + '.$event->order->amount),
            'updated_at' => Carbon::now()
        ]);

        //2.处理上级 下级充值总额  下级今日充值总额
        $user_ids = explode(',', trim($event->order->user->relation, ','));
        if (count($user_ids)) {
            UserStatistic::query()->whereIn('user_id',$user_ids)->update([
                'subordinate_recharge' => DB::raw('`subordinate_recharge` + '.$event->order->amount),
                'subordinate_today_recharge' => DB::raw('`subordinate_today_recharge` + '.$event->order->amount),
                'updated_at' => Carbon::now()
            ]);
        }

    }
}
