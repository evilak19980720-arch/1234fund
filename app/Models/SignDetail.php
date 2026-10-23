<?php

namespace App\Models;

use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SignDetail extends Model
{
    use DefaultDatetimeFormat;


    protected $fillable = ['user_id', 'sign_time', 'amount'];


    //用户
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    /**
     * 签到功能
     * @param User $user 用户模型
     * @return void
     */
    public function sign(User $user)
    {

        DB::transaction(function () use ($user){
            if (self::query()->where('user_id', '=', $user->id)->whereDate('sign_time', '=', Carbon::today())->count()) {
                return 10032;
            }
            $amount = Configuration::getItem('sign_in_reward');
            //插入签到信息
            self::query()->create([
                'user_id' => $user->id,
                'sign_time' => Carbon::now(),
                'amount' => $amount
            ]);
            //1.增加用户余额

            $user->increment('balance', $amount);
            //2.增加流水
            Account::query()->create([
                'type' => Account::SIGN,
                'user_id' => $user->id,
                'amount' => $amount
            ]);
        });
    }
}
