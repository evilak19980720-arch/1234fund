<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class FundAmountAccount extends Model
{
    const TYPE_ADMIN_INCREASE = 0; //后台加钱
    const TYPE_ADMIN_DECREASE = 1; //后台减钱
    const TYPE_FUND_RETURN = 2; //前台买彩票返水

    const TYPE_TEXT_ARR = [
        self::TYPE_ADMIN_INCREASE   => '后台增加',
        self::TYPE_ADMIN_DECREASE   => '后台扣除',
        self::TYPE_FUND_RETURN      => '前台众筹返水',
    ];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    function user()
    {
        return $this->belongsTo(User::class, 'to');
    }

    static function handle(array $fundOrderArr, User $me) : void
    {
        $ratio = Configuration::getItem('fund_ratio');
        $ratio = bcdiv((string)$ratio, 100, 2);

        foreach ($fundOrderArr as $fundOrder) {
            if (! $fundOrder) { //调用者处出现了传递空数组的情况
                continue;
            }
            $amount = bcmul((string)$fundOrder['amount'], $ratio, 0);
            if ($me->fund_amount >= $amount && $amount >= 1) {
                //1. 扣除用户众筹余额&增加用户余额
                $me->decrement('fund_amount', $amount);
                $me->increment('balance', $amount);
                //2. 写众筹流水
                $fundAmountAccount = new FundAmountAccount;
                $fundAmountAccount->to              = $me->id;
                $fundAmountAccount->fund_order_id   = $fundOrder['id'];
                $fundAmountAccount->type            = self::TYPE_FUND_RETURN;
                $fundAmountAccount->amount          = $amount;
                $fundAmountAccount->save();
                //3. 写一条Accounts流水,
                $account = new Account;
                $account->order_id  = $fundOrder['id'];
                $account->type      = Account::FUNDING_RETURN;
                $account->to        = $me->id;
                $account->amount    = $amount;
                $account->save();
            }
        }
    }
}
