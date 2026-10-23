<?php


namespace App\Logic;


use App\Handlers\KeysHandler;
use App\Models\User;
use App\Models\Vip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class PayLogic extends Logic
{
    /**
     * 检查每日提现上限
     * @param User $user 用户
     * @param int $amount 提现金额
     * @return int  错误码
     */
    public function checkDailyWithdrawalUpper(User $user, int $amount)
    {
        $key = sprintf(KeysHandler::TodayWithdrawalUpperLimit, $user->id, Carbon::now()->format('Y-m-d'));
        $vip = Vip::getVip($user->vip_id);

        $out = Cache::get($key);
        if (null === $out) {
            if ($amount > $vip->daily_cash_out_amount) {
                $this->error = 60011;
            } else {
                Cache::put($key, $amount, $this->cache24Hour());
            }
        } else {
            if (($amount + $out) > $vip->daily_cash_out_amount) {
                $this->error = 60011;
            } else {
                Cache::increment($key, $amount);
            }
        }

        return $this->error;
    }
}
