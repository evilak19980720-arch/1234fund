<?php

namespace App\Jobs;

use App\Admin\Actions\Order\Log;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Pet;
use App\Models\PetOrder;
use App\Models\Rule;
use App\Models\User;
use App\Models\UserStatistic;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SellDayQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pet_order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($pet_order)
    {
        $this->pet_order = $pet_order;
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     */
    public function handle()
    {

        if(config('app.revenue_model') != 'day'){
            return;
        }

        $pet_order = $this->pet_order;
        //周期结算
        DB::transaction(function () use ($pet_order) {
            //查询当前订单
            info('开始结算订单:'.$pet_order->id);
            $pet_order = PetOrder::where(['id' => $pet_order->id])->sharedLock()->first();
            if ($pet_order->state == 0 && $pet_order->sell_day < $pet_order->day) {
                if(Account::query()->where('to',$pet_order->user_id)->where('order_id',$pet_order->id)->where('type',13)->whereDate('created_at',Carbon::now())->first()){
                    info('收益今日已结算,会员'.$pet_order->user_id.',订单:'.$pet_order->id);
                    return false;
                }
                $user = User::query()->where('id','=',$pet_order->user_id)->first();
                //$pet = Pet::find($pet_order->pet_id);
                //$pet->increment('waiting_quantity', $pet_order->number);
                //每天收益金额
                $amount =  $pet_order->selling_amount;
                //增加用户余额
                $user->increment('balance', $amount);
                $user->increment('revenue', $amount);
                //添加流水记录
                Account::create([
                    'order_id' => $pet_order->id,
                    'type' => 13,
                    'to' => $pet_order->user_id,
                    'amount' => $amount,
                ]);

                $pet_order->sell_day = $pet_order->sell_day + 1;
                if($pet_order->sell_day >= $pet_order->day){
                    //当前订单状态设置为已卖出
                    $pet_order->state = 1;
                }
                $pet_order->save();

                //计算上级分佣
                /*
                if (Configuration::getItem('profit_rebate') && $user->profit_rebate && $user->relation) {
                    $uids = [];
                    if (!empty($user->relation)) {
                        $uids = explode(',', trim($user->relation, ','));
                    }
                    if (!$uids) {
                        return $user;
                    }
                    $uids = array_reverse($uids);
                    $rules = array_values(Rule::getTaskRule());
                    foreach ($uids as $k => $v) {
                        $money = $amount * $rules[$k]['percent'] * 0.01;
                        User::where('id', $v)->increment('team_commission', $money);
                        Account::create([
                            'type' => Account::RIncome,
                            'to' => $v,
                            'amount' => $money,
                            'from' => $user->id
                        ]);
                        //增加会员统计的返佣信息
                        $user = User::query()->where('id','=',$v)->first();
                        (new UserStatistic())->store($user);
                        if ($user_statistic = UserStatistic::query()->where('user_id','=',$v)->first()) {
                            $user_statistic->increment('commission',$money);
                        }
                    }
                }
                */
            }
            return 'SettleAccounts';
        });
    }
}
