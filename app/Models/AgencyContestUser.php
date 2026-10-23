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
class AgencyContestUser extends Model
{

    use DefaultDatetimeFormat;
    protected $table='agency_contest_users';
    protected $fillable = [
        'agency_contest_id', 'user_id', 'active_member_count', 'accumulated_commission', 'dayadd_active_member_count', 'dayadd_accumulated_commission', 'isfirst', 'is_receive_award','user_phone','whatsapp','telegram'
    ];

    const RankCacheKey = 'rank_cache_key:%d';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agency_contest()
    {
        return $this->belongsTo(AgencyContest::class,'agency_contest_id','id');
    }


    public static function getAllUserRank($agency_contest_id, $id = null)
    {
        $key = sprintf(AgencyContestUser::RankCacheKey, $agency_contest_id);
        if (!Cache::has($key)) {
            $rank = self::query()
                ->where('agency_contest_id','=',$agency_contest_id)
                ->orderByDesc('isfirst')
                ->orderByDesc('active_member_count')
                ->orderByDesc('accumulated_commission')
                ->orderBy('created_at')
                ->get();
            Cache::put($key, $rank);
        }
        $rank = Cache::get($key);

        if($rank->isNotEmpty()){
            foreach ($rank as $key=>&$val){
                $val->rank = $key+1;
                if(!empty($id) && $id == $val->id){
                    return $val;
                }
            }
        }
        return $rank;
    }




    //有会员首次投资
    public static function userJoin($user)
    {
        //查有没有进行中的活动
        $agency_contest = AgencyContest::query()->where('start_time','<',now())->where('end_time','>',now())->where('status','=',1)->orderByDesc('id')->first();
        if($agency_contest)
        {
           $AgencyContestRewardModel = Configuration::getItem('AgencyContestRewardModel');
           if($AgencyContestRewardModel == 3){  //三级模式
               if ($user->relation)
               {
                   $user_ids = explode(',', trim($user->relation, ','));
                   while (count($user_ids)) {
                       $uid = array_pop($user_ids);
                       $this_user = User::query()->find($uid);
                       if($this_user->hierarchy_id != 1){
                           continue;
                       }
                       $agency_contest_user = self::query()->where('agency_contest_id','=',$agency_contest->id)->where('user_id','=',$uid)->first();
                       if($agency_contest_user){
                           $agency_contest_user->increment('active_member_count',1);
                       }else{
                           self::query()->create([
                               'active_member_count' => 1,
                               'agency_contest_id' => $agency_contest->id,
                               'user_id' => $this_user->id,
                               'user_phone' => $this_user->phone,
                           ]);
                       }
                   }
               }
           }
            if($AgencyContestRewardModel == 1){
                if ($user->parent_id)
                {
                    $uid = $user->parent_id;
                    $this_user = User::query()->find($uid);
                    if($this_user->hierarchy_id == 1){
                        $agency_contest_user = self::query()->where('agency_contest_id','=',$agency_contest->id)->where('user_id','=',$uid)->first();
                        if($agency_contest_user){
                            $agency_contest_user->increment('active_member_count',1);
                        }else{
                            self::query()->create([
                                'active_member_count' => 1,
                                'agency_contest_id' => $agency_contest->id,
                                'user_id' => $this_user->id,
                                'user_phone' => $this_user->phone,
                            ]);
                        }
                    }

                }
            }


        }

    }


    public static function userJoinCommission($this_user, $money){

        if($this_user->hierarchy_id != 1){
            return;
        }
        //查有没有进行中的活动
        $agency_contest = AgencyContest::query()->where('start_time','<',now())->where('end_time','>',now())->where('status','=',1)->orderByDesc('id')->first();
        if($agency_contest)
        {
            $agency_contest_user = self::query()->where('agency_contest_id','=',$agency_contest->id)->where('user_id','=',$this_user->id)->first();
            if($agency_contest_user){
                $agency_contest_user->increment('accumulated_commission',$money);
            }else{
                self::query()->create([
                    'accumulated_commission' => $money,
                    'agency_contest_id' => $agency_contest->id,
                    'user_id' => $this_user->id,
                    'user_phone' => $this_user->phone,
                ]);
            }
        }
    }



}
