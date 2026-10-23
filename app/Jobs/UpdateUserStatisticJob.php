<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Order;
use App\Models\User;
use App\Models\UserStatistic;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdateUserStatisticJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected UserStatistic $userStatistic;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(UserStatistic $statistic)
    {
        $this->userStatistic = $statistic;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::transaction(function(){
            $user_statistic = UserStatistic::query()->sharedLock()->find($this->userStatistic->id);

            $one_level = $this->getYesterdayRegisterSubordinate($user_statistic->user_id,1);
            $two_level = $this->getYesterdayRegisterSubordinate($user_statistic->user_id,2);
            $three_level = $this->getYesterdayRegisterSubordinate($user_statistic->user_id,3);
            $all_subordinate = array_merge($one_level,$two_level,$three_level);

            $user_statistic->recharge = Order::query()->where('user_id','=',$user_statistic->user_id)->where('type','=',1)->where('state','=',Order::SUCCESS)->sum('amount');
            $user_statistic->today_recharge = Order::query()->where('user_id','=',$user_statistic->user_id)->where('type','=',1)->where('state','=',Order::SUCCESS)->whereDate('created_at','=',Carbon::today())->sum('amount');
            $user_statistic->commission = Account::query()->whereIn('type',[Account::RIncome,Account::AGENCY_COMMISSION])->where('to','=',$user_statistic->user_id)->sum('amount');
            $user_statistic->one_level = count($one_level);
            $user_statistic->two_level = count($two_level);
            $user_statistic->three_level = count($three_level);
            $user_statistic->subordinate_total = count($all_subordinate);
            $user_statistic->subordinate_recharge = count($all_subordinate) ? Order::query()->whereIn('user_id',$all_subordinate)->where('type','=',1)->where('state','=',Order::SUCCESS)->sum('amount') : 0;
            $user_statistic->subordinate_today_recharge = count($all_subordinate) ? Order::query()->whereIn('user_id',$all_subordinate)->where('type','=',1)->where('state','=',Order::SUCCESS)->whereDate('created_at','=',Carbon::today())->sum('amount') : 0;
            $user_statistic->save();
        });

    }

    //获取注册的下级人数
    protected function getYesterdayRegisterSubordinate(int $user_id,int $level): array
    {
        $condition = sprintf(',%d,%s$',$user_id, str_repeat('[0-9]+,', $level - 1));
        return User::query()->where('relation', 'regexp', $condition)->pluck('id')->toArray();
    }
}
