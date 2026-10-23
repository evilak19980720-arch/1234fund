<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RegisterReward extends Model
{
    protected $fillable = ['user_id', 'amount', 'unlock'];
    //解锁状态
    const UNLOCK_FALSE = 0;//否
    const UNLOCK_TRUE = 1;//是


    //注册赠送不可用余额
    public function store(User $user)
    {
        if (Configuration::getItem('register_frozen_reward_switch') && Configuration::getItem('register_frozen_reward') && Configuration::getItem('register_frozen_activate')) {
            DB::transaction(function () use ($user) {
                if (self::query()->where('user_id', '=', $user->id)->count()) {
                    return true;
                }
                $amount = Configuration::getItem('register_frozen_reward');
                $user->frozen = $user->frozen + $amount;
                $user->save();
                //插入记录
                self::query()->create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'unlock' => self::UNLOCK_FALSE
                ]);
            });
        }
    }
}
