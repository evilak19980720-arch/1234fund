<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * 活动
 * @package App\Models
 */
class Activity extends Model
{
    use SoftDeletes;
    use DefaultDatetimeFormat;

    //状态
    const STATUS_OFF = 0;
    const STATUS_ON = 1;

    //type类型
    const INVITE_FRIENDS = 0;//邀请好友
    const INVITE_FRIENDS_AND_SUBORDINATE_GRAB = 1;//邀请好友并且其中一个下级购买成功
    const INVITE_FRIENDS_AND_CARD = 2;//邀请好友并且绑定银行卡
    const INVITE_FRIENDS_AND_RECHARGE = 3;//邀请好友并且充值
    const INVITE_FRIENDS_AND_SUBPETORDER = 5;//邀请好友并且绑定银行卡
    const SIGN = 4;//签到
    const DOWN = 7;//下载

    const INVITE_FRIENDS_AND_DOWNAPP = 6;//邀请好友并且下载APP

    const TYPE_MAP = [
        //self::INVITE_FRIENDS => '邀请好友',
        //self::INVITE_FRIENDS_AND_SUBORDINATE_GRAB => '邀请好友并且下级购买成功',
        self::INVITE_FRIENDS_AND_CARD => '邀请好友并且绑定银行卡',
        self::INVITE_FRIENDS_AND_RECHARGE => '邀请好友并且充值',
        self::SIGN => '签到',
        self::INVITE_FRIENDS_AND_SUBPETORDER => "邀请好友并且购买",
        self::DOWN => '下载',
        self::INVITE_FRIENDS_AND_DOWNAPP => "邀请好友并且下载"
    ];

    public function users()
    {
        return $this->hasMany(ActivityUser::class,'activity_id');
    }


    public static function getActivity($id = 0)
    {
        $key = $id ? sprintf(KeysHandler::ACTIVITY, $id) : KeysHandler::ACTIVITIES;

        $activity = Cache::get($key);
        if (!$activity) {
            if ($id) {
                $activity = self::where('id', '=', $id)->first();
            } else {
                $activity = self::where('status', '=', 1)->get();
            }
            $activity && Cache::put($key, $activity, 60 * 5);
        }

        return $activity;
    }

    //获取邀请任务的下级
    public function invite(User $user, Activity $activity)
    {
        //判断 需先完成的任务 的条件限制
        $child = collect([]);
        if ($activity->condition) {
            //如果有 需先完成的任务限制 就查询出限制任务的完成时间
            $created_at = ActivityUser::query()
                ->where('user_id', '=', $user->id)
                ->where('activity_id', '=', $activity->condition)
                ->value('created_at');
            if ($created_at) {
                $child = User::query()
                    ->where('relation', 'regexp', sprintf(',%d,%s$', $user->id, str_repeat('[0-9]+,', 0)))
                    ->where('created_at', '>=', $created_at)
                    ->get();
            }
        } else {
            $child = User::query()
                ->where('relation', 'regexp', sprintf(',%d,%s$', $user->id, str_repeat('[0-9]+,', 0)))
                ->get();
        }
        return $child;
    }


    //是否领取了签到奖励
    public function receivedSignReward(User $user , $activity_id)
    {
        $received = false;
        $key = sprintf(KeysHandler::ACTIVITY_RECEIVED_SIGN, $activity_id, $user->id, Carbon::today()->format('Ymd'));
        $received = Cache::get($key);
        if (!Cache::has($key)) {
            $received = ActivityUser::query()->where('activity_id', '=', $activity_id)
                ->where('user_id', '=', $user->id)
                ->whereDate('created_at','=',Carbon::today())
                ->count();
            Cache::put($key, $received, 60 * 60 * 24);
        }
        $received = $received >= 1;
        return $received;
    }



}
