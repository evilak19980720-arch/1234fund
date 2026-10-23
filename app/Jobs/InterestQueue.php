<?php

namespace App\Jobs;

use App\Models\Interest;
use App\Models\InterestLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Configuration;
use App\Models\InterestRevenue;

class InterestQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_id)
    {
        $this -> user_id = $user_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     * 金额确认
     * 利息宝 收益计算
     */
    public function handle()
    {
        $user_id = $this->user_id;
        //金额确认
        DB::transaction(function () use ($user_id) {
            $all_amount = InterestLog::where('user_id','=',$user_id)->where('confirm',0)->where('created_at','<',Carbon::now()->subDays(1))->sharedLock()->sum('amount');
            if($all_amount > 0){
                Interest::where('user_id','=',$user_id)->increment('confirm_balance',$all_amount);
                InterestLog::where('user_id','=',$user_id)->where('confirm',0)->where('created_at','<',Carbon::now()->subDays(1))->update(['confirm'=>1]);
            }
        });

        //利息宝 收益计算
        $interest_profit = Configuration::getItem('interest_profit');
        if ($interest_profit == 1) {
            DB::transaction(function () use ($user_id) {
                $revenve = InterestRevenue::whereDate('created_at', Carbon::today())->where('user_id',$user_id)->first();
                if($revenve){ return; }
                $interest = Interest::where('user_id','=',$user_id)->sharedLock()->first();
                if($interest && $interest -> confirm_balance > 100 && $interest ->revenue_switch == 1) { //大于5分钱时计算
                    $interest_annual_yield = Configuration::getItem('interest_annual_yield');
                    $revenue = $interest -> confirm_balance * $interest_annual_yield / 100 /365;
                    if($revenue >= 1){
                        $interest->balance = $interest->balance + $revenue;
                        $interest->confirm_balance = $interest->confirm_balance + $revenue;
                        $interest->update();
                        InterestRevenue::create([
                            'user_id' => $user_id,
                            'amount' => $revenue,
                            'balance' => $interest->balance,
                            'confirm' => 1
                        ]);
                    }
                }

            });
        }


    }
}
