<?php

namespace App\Jobs;

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

class SettleAccountsQueue implements ShouldQueue
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
        $pet_order = $this->pet_order;
        //周期结算
        DB::transaction(function () use ($pet_order) {
            //查询当前订单
            $pet_order = PetOrder::where(['id' => $pet_order->id])->sharedLock()->first();
            if ($pet_order->state == 0) {
                //当前订单状态设置为已卖出
                $pet_order->state = 1;
                $pet_order->save();
                //增加等待合成下一级的数量
                $pet = Pet::find($pet_order->pet_id);

                $pet->increment('waiting_quantity', $pet_order->number);
                //卖出金额
                $amount = $pet_order->selling_amount;
                //增加售出的订单
                PetOrder::create([
                    'user_id' => $pet_order->user_id,
                    'pet_id' => $pet_order->pet_id,
                    'no' => only_code(),
                    'price' => $pet_order->selling_price,
                    'number' => $pet_order->number,
                    'day' => $pet_order->day,
                    'amount' => $amount,
                    'profit_amount' => $pet_order->profit_amount,
                    'selling_price' => $pet_order->selling_price,
                    'selling_amount' => $pet_order->selling_amount,
                    'type' => 3,
                ]);

                $profit = $pet_order->profit_amount;

                //增加用户余额
                $user = User::find($pet_order->user_id);
                $user->increment('balance', $amount);
                $user->increment('revenue', $profit);
                //添加流水记录
                Account::create([
                    'type' => 13,
                    'to' => $pet_order->user_id,
                    'amount' => $amount,
                ]);
                //计算上级分佣
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
                        $money = $profit * $rules[$k]['percent'] * 0.01;
                        User::where('id', $v)->increment('balance', $money);
                        Account::create([
                            'type' => 3,
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
            }
            return 'SettleAccounts';
        });
    }
}
