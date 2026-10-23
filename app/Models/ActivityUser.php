<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 活动领取记录
 * @package App\Models
 */
class ActivityUser extends Model
{
    protected $table='activity_user';

    protected $fillable = [
        'user_id', 'activity_id', 'amount'
    ];

    public function activites()
    {
        return $this->belongsTo(Activity::class,'activity_id','id');
    }

    public function receive($user, $activity)
    {
        return DB::transaction(function () use ($user, $activity) {
            $user = User::query()->where('id', '=', $user->id)->lockForUpdate()->first();
            switch ($activity->type) {
                //签到
                case Activity::SIGN:
                    if(app(Activity::class)->receivedSignReward($user, $activity->id)){
                        return 70002;
                    }
                    break;
                default:
                    if (ActivityUser::query()->where('activity_id', '=', $activity->id)->where('user_id', '=', $user->id)->count() >= $activity->num) {
                        return 70002;
                    }
                    break;
            }

            $amount = $activity->amount;
            // 领取记录
            $this->user_id = $user->id;
            $this->activity_id = $activity->id;
            $this->amount = $amount;
            $this->save();
            // 加余额
            $user->increment('balance', $amount);
            // 流水
            Account::create([
                'type' => Account::REWARD,
                'to' => $user->id,
                'amount' => $amount,
            ]);

            switch ($activity->type) {
                //签到
                case Activity::SIGN:
                    $key = sprintf(KeysHandler::ACTIVITY_RECEIVED_SIGN, $activity->id, $user->id, Carbon::today()->format('Ymd'));
                    Cache::forget($key);
                    break;

                case Activity::INVITE_FRIENDS_AND_CARD:
                    //扣除占用人数
                    $user->decrement('sub_first_bindcard', $activity->number);
                    break;
                case Activity::INVITE_FRIENDS_AND_SUBPETORDER:
                    //扣除占用人数
                    $user->decrement('sub_first_petorder', $activity->number);
                    break;
                case Activity::INVITE_FRIENDS_AND_DOWNAPP:
                    //扣除占用人数
                    $user->decrement('sub_first_downapp', $activity->number);
                    break;
                default:
                    break;
            }
            return true;
        });
    }
}
