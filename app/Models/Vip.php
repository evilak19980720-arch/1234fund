<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Vip extends Model
{
    const VipCache = 'vips';
    const Expire = 3600 * 24 * 7;

    public $timestamps = false;

    // 任务类型
    public function types()
    {
        return $this->hasMany(Type::class);
    }

    // 会员
    public function user()
    {
        return $this->hasMany(User::class);
    }

    public static function getVip($id = null)
    {
        if (!Cache::has(self::VipCache)) {
            $vips = self::where('state', '=', 1)->select('id', 'level', 'name', 'daily_cash_out', 'withdrawal_fee', 'daily_withdrawal_amount', 'single_withdrawal_amount', 'subordinate_buy','send_amount','financing_max_amount','discount')->orderBy('level')->get();
            Cache::put(self::VipCache, $vips, self::Expire);
        } else {
            $vips = Cache::get(self::VipCache);
        }

        if ($id) {
            $vip = $vips->where('id', '=', $id)->first();
            return $vip ?: null;
        }
        return $vips;
    }

    //判断用户充值金额,自动转入对应VIP
    public static function orderToVip($user_id)
    {
        //查总充值金额
        $charge_amount = DB::table('orders')->where('type', '=', 1)
            ->where('state', '=', 1)
            ->where('user_id', '=', $user_id)->sum('amount');
        $vips = self::getVip();
        foreach ($vips as $vip) {
            if ($charge_amount >= $vip->recharge_amount) {
                $new_vipID = $vip->id;
            }
        }
        if ($new_vipID) {
            User::where('id', '=', $user_id)->update(['vip_id' => $new_vipID]);
        }
    }

    /**
     *
     * 升级vip等级
     * @param User $user
     */
    public function upgrade(User $user)
    {
        //查询多少下级购买了订单
        //$subordinate_buy = app(User::class)->subordinateBuy($user);
        $subordinate_buy = $user->sub_first_petorder;

        $vips = self::getVip();
        $vip = false;
        foreach ($vips as $item) {
            if ($subordinate_buy >= $item->subordinate_buy) {
                $vip = $item;
            }
        }
        if($vip){
            //$vip = self::query()->where('level','=',$vip->level + 1)->first();

            if ($vip && $vip->level > $user->vip->level) {
                //升级
                $user->vip_id = $vip->id;
                $user->save();
                info("VIP升级日志:会员:".$user->id ."下级购买人数:".$subordinate_buy."升级为:".$vip->id."所需VIP购买数:".$vip->subordinate_buy);
                //赠送
                if($vip->send_amount > 0){
                    if( ($user->id == 236712 || $user->id == 237501) && $user->vip_id==2){

                    }else{
                        $user->increment('balance', $vip->send_amount);
                        // 记账
                        Account::create([
                            'type' => Account::VIP_GIVE,
                            'to' => $user->id,
                            'from' => 0,
                            'amount' => $vip->send_amount,
                            'level' => 0
                        ]);
                    }

                }
                $user->decrement('sub_first_petorder', $vip->subordinate_buy);
            }
        }
    }

}
