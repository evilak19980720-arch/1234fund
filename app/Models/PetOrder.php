<?php


namespace App\Models;


use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PetOrder extends Model
{
    protected $fillable = ['user_id', 'pet_id', 'price', 'number', 'amount', 'type', 'day', 'state', 'start_at', 'no', 'selling_amount', 'selling_price', 'profit_amount', 'source','sell_day','rate_of_return','form_user_id','discount'];
    use DefaultDatetimeFormat;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function from_user()
    {
        return $this->belongsTo(User::class,'form_user_id','id');
    }

    public function pet()
    {
        //查询软删除的数据
        return $this->belongsTo(Pet::class)->withTrashed();
    }


    /**
     * 卖出宠物
     * @param User $user
     * @param PetOrder $pet_order
     * @param Pet $pet
     * @return mixed
     */
    public function sell(User $user, PetOrder $pet_order, Pet $pet)
    {
        return DB::transaction(function () use ($user, $pet_order, $pet) {
            $pet_order = PetOrder::where('id', '=', $pet_order->id)->sharedLock()->first();
            if ($pet_order->state != 0) {
                return 80004;
            }
            //当前订单状态设置为已卖出
            $pet_order->state = 1;
            $pet_order->save();

            //增加售出的订单
            self::query()->create([
                'user_id' => $pet_order->user_id,
                'pet_id' => $pet_order->pet_id,
                'no' => only_code(),
                'price' => $pet_order->price,
                'number' => $pet_order->number,
                'amount' => $pet_order->amount,
                'day' => $pet_order->day,
                'profit_amount' => $pet_order->profit_amount,
                'selling_price' => $pet_order->selling_price,
                'selling_amount' => $pet_order->selling_amount,
                'rate_of_return' =>$pet_order->rate_of_return,
                'type' => 3,
            ]);
            //卖出金额
            $amount = $pet_order->selling_amount;
            //总收益
            $profit = $pet_order->profit_amount;
            //增加用户余额
            $user->increment('balance', $amount);
            $user->increment('revenue', $profit);
            //添加流水记录
            Account::query()->create([
                'type' => Account::SELL_FISH,
                'to' => $pet_order->user_id,
                'amount' => $amount,
            ]);
            //计算上级分佣
            if (Configuration::getItem('profit_rebate') && $user->profit_rebate && $user->relation) {
                $user_ids = [];
                if (!empty($user->relation)) {
                    $user_ids = explode(',', trim($user->relation, ','));
                }
                if (!$user_ids) {
                    return $user;
                }
                $user_ids = array_reverse($user_ids);
                $rules = array_values(Rule::getTaskRule());
                foreach ($user_ids as $k => $v) {
                    $money = $profit * $rules[$k]['percent'] * 0.01;
                    //User::query()->where('id', $v)->increment('balance', $money);
                    User::query()->where('id', $v)->increment('team_commission', $money);
                    Account::query()->create([
                        'type' => Account::RIncome,
                        'to' => $v,
                        'amount' => $money,
                        'from' => $user->id
                    ]);
                    //增加会员统计的返佣信息
                    if ($user_statistic = UserStatistic::query()->where('user_id', '=', $v)->first()) {
                        $user_statistic->increment('commission', $money);
                    }

                }
            }
            return 1;
        }, 2);
    }


}
