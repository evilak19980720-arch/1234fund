<?php

namespace App\Http\Controllers\Api\v1;

use App\Handlers\KeysHandler;
use App\Models\AgencyContest;
use App\Models\AgencyContestUser;
use App\Models\Configuration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Account;
use function Symfony\Component\String\b;


/**
 * 代理争霸
 * @package App\Http\Controllers\Api\v1
 */
class AgencyContestController extends Controller
{

    /**
     * 数据展示
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|object
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $data = Cache::remember('AgencyContestShow:'.$user->id, 10,function () use($user){
            $last_agency_contest = null;

            $agency_contest = AgencyContest::query()
                ->select(['id','issue','start_time','end_time','reward','status'])
                ->with(
                    [
                        'agency_contest_users'=>function ($q){
                            $q->select(['user_phone','active_member_count','accumulated_commission','agency_contest_id','user_id','isfirst','is_receive_award']);
                        },
                        'agency_contest_first_user'=>function ($q){
                            $q->select(['user_phone','active_member_count','accumulated_commission','agency_contest_id','user_id','is_receive_award','whatsapp','telegram','isfirst']);
                        },
                    ]
                )
                ->where('start_time','<',now())
                ->orderByDesc('id')->first();


            if($agency_contest)
            {
                $agency_contest->myrank = '--';
                $agency_contest->myranktofirst = 0;
                $agency_contest->receiveBtn = 0;
                $agency_contest->receiveBtnText = transtable('text.receive');

                //获胜  没结束时 永远是亮的
                if($agency_contest->status == 1)
                {
                    $agency_contest->receiveBtn = 1;
                }

                $agency_contest->start_time = Carbon::parse($agency_contest->start_time)->timestamp;
                $agency_contest->end_time = Carbon::parse($agency_contest->end_time)->timestamp;
                $agency_contest_users = $agency_contest->agency_contest_users;
                if(count($agency_contest_users) > 0)
                {
                    $first_users = $agency_contest->agency_contest_users[0];
                    //本人没有参与, 有人参与过, 第一名的数量加1
                    $agency_contest->myranktofirst = $first_users->active_member_count + 1;
                    foreach ($agency_contest_users as $key=>&$val)
                    {
                        $val->user_phone = sprintf('%s****%s', substr($val->user_phone, 0, 3), substr($val->user_phone, -3));
                        if($user->id == $val->user_id)
                        {
                            $agency_contest->myrank = $key+1;
                            //本人参与过,第一名的数量减自己数量+1
                            $agency_contest->myranktofirst = $first_users->active_member_count - $val->active_member_count +1;
                        }
                        //可领取
                        if($user->id == $val->user_id && $agency_contest->status == 2 && $val->isfirst >= 1 && $val->is_receive_award == 0)
                        {
                            $agency_contest->receiveBtn = 1;
                        }

                        //已领取
                        if($user->id == $val->user_id && $agency_contest->status == 2 && $val->isfirst >= 1 && $val->is_receive_award == 1)
                        {
                            $agency_contest->receiveBtnText = transtable('text.received');
                        }

                    }
                }

                if ($agency_contest->agency_contest_first_user){

                    //本人是第一名 需要0人
                    if($user->id == $agency_contest->agency_contest_first_user->user_id )
                    {
                        $agency_contest->myranktofirst = 0;
                    }

                    //第一名隐藏手机号
                    if($agency_contest->agency_contest_first_user->user_id > 0){
                        $f_user = $agency_contest->agency_contest_first_user->user;
                        $agency_contest->agency_contest_first_user->whatsapp_hide = $f_user->whatsapp_hide;
                        $agency_contest->agency_contest_first_user->telegram_hide = $f_user->telegram_hide;;
                        if($f_user->whatsapp_hide == 1){
                            $agency_contest->agency_contest_first_user->user_phone = sprintf('%s****%s', substr($agency_contest->agency_contest_first_user->user_phone, 0, 3), substr($agency_contest->agency_contest_first_user->user_phone, -3));
                        }
                        if($f_user->whatsapp_hide == 0){
                            $agency_contest->agency_contest_first_user->whatsapp = $f_user->whatsapp;
                        }
                        if($f_user->telegram_hide == 0){
                            $agency_contest->agency_contest_first_user->telegram = $f_user->telegram;
                        }
                    }else{
                        $agency_contest->agency_contest_first_user->whatsapp_hide = 1;
                        $agency_contest->agency_contest_first_user->telegram_hide = 1;
                        if(!empty($agency_contest->agency_contest_first_user->whatsapp)){
                            $agency_contest->agency_contest_first_user->whatsapp_hide = 0;
                        }
                        if(!empty($agency_contest->agency_contest_first_user->telegram)){
                            $agency_contest->agency_contest_first_user->telegram_hide = 0;
                        }
                        if($agency_contest->agency_contest_first_user->whatsapp_hide == 1){
                            $agency_contest->agency_contest_first_user->user_phone = sprintf('%s****%s', substr($agency_contest->agency_contest_first_user->user_phone, 0, 3), substr($agency_contest->agency_contest_first_user->user_phone, -3));
                        }

                    }




                }



                //上期
                $last_agency_contest = AgencyContest::query()
                    ->select(['id','issue','start_time','start_time','end_time','reward','status'])
                    ->with(
                        [
                            'agency_contest_first_user'=>function ($q){
                                $q->select(['user_phone','active_member_count','accumulated_commission','agency_contest_id','user_id','whatsapp','telegram']);
                            }
                        ]
                    )
                    ->where('start_time','<',now())
                    ->where('id','<',$agency_contest->id)
                    ->orderByDesc('id')->first();

                if($last_agency_contest){
                    $last_agency_contest->start_time = Carbon::parse($last_agency_contest->start_time)->timestamp;
                    $last_agency_contest->end_time = Carbon::parse($last_agency_contest->end_time)->timestamp;
                    if(!empty($last_agency_contest->agency_contest_first_user)){
                        //第一名隐藏手机号
                        if($last_agency_contest->agency_contest_first_user->user_id > 0){
                            $last_f_user = $last_agency_contest->agency_contest_first_user->user;
                            $last_agency_contest->agency_contest_first_user->whatsapp_hide = $last_f_user->whatsapp_hide;
                            $last_agency_contest->agency_contest_first_user->telegram_hide = $last_f_user->telegram_hide;
                            if($last_f_user->whatsapp_hide == 1){
                                $last_agency_contest->agency_contest_first_user->user_phone = sprintf('%s****%s', substr($last_agency_contest->agency_contest_first_user->user_phone, 0, 3), substr($last_agency_contest->agency_contest_first_user->user_phone, -3));
                            }
                            if($last_f_user->whatsapp_hide == 0){
                                $last_agency_contest->agency_contest_first_user->whatsapp = $last_f_user->whatsapp;
                            }
                            if($last_f_user->telegram_hide == 0){
                                $last_agency_contest->agency_contest_first_user->telegram = $last_f_user->telegram;
                            }
                        }else{
                            $last_agency_contest->agency_contest_first_user->whatsapp_hide = 1;
                            $last_agency_contest->agency_contest_first_user->telegram_hide = 1;
                            if(!empty($last_agency_contest->agency_contest_first_user->whatsapp)){
                                $last_agency_contest->agency_contest_first_user->whatsapp_hide = 0;
                            }
                            if(!empty($last_agency_contest->agency_contest_first_user->telegram)){
                                $last_agency_contest->agency_contest_first_user->telegram_hide = 0;
                            }
                            if($last_agency_contest->agency_contest_first_user->whatsapp_hide == 1){
                                $last_agency_contest->agency_contest_first_user->user_phone = sprintf('%s****%s', substr($last_agency_contest->agency_contest_first_user->user_phone, 0, 3), substr($last_agency_contest->agency_contest_first_user->user_phone, -3));
                            }
                        }
                    }


                }


            }



            /*
            if($last_agency_contest && !empty($last_agency_contest->agency_contest_first_user)){
                $last_agency_contest->agency_contest_first_user->user_phone = sprintf('%s****%s', substr($last_agency_contest->agency_contest_first_user->user_phone, 0, 3), substr($last_agency_contest->agency_contest_first_user->user_phone, -3));
            }
            */
            //未开始
            $next_agency_contest = AgencyContest::query()
                ->select(['id','issue','start_time','start_time','end_time','reward','status'])
                ->where('start_time','>',now())
                ->orderBy('start_time')->orderByDesc('id')->first();
            if($next_agency_contest){
                $next_agency_contest->start_time = Carbon::parse($next_agency_contest->start_time)->timestamp;
                $next_agency_contest->end_time = Carbon::parse($next_agency_contest->end_time)->timestamp;
            }


            $data['agency_contest'] = $agency_contest ?? [];
            $data['last_agency_contest'] = $last_agency_contest ?? [];
            $data['next_agency_contest'] = $next_agency_contest ?? [];
            return $data;
        });


        return $this->success($data);
    }


    /**
     * 领取奖金
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|object
     */
    public function reward(Request $request)
    {
        $user = $request->user();
        $res =  DB::transaction(function () use ($user) {
            $agency_contest_user = AgencyContestUser::query()
                ->where('user_id', '=', $user->id)
                ->where('isfirst', '>=', 1)
                ->orderByDesc('id')
                ->lockForUpdate()->first();
            if(!$agency_contest_user){
                return 70000;
            }

            if($agency_contest_user->is_receive_award == 1){
                return 70002;
            }
            $AgencyContestRewardModel = Configuration::getItem('AgencyContestRewardModel');

            $agency_contest = AgencyContest::query()->where('id','=',$agency_contest_user->agency_contest_id)->first();

            if($AgencyContestRewardModel == 3){
                $reward = $agency_contest->reward;
            }
            if($AgencyContestRewardModel == 1){
                switch ($agency_contest_user->isfirst){
                    case 3:
                        $reward = bcmul($agency_contest->reward, 0.5);
                        break;
                    case 2:
                        $reward = bcmul($agency_contest->reward, 0.3);
                        break;
                    case 1:
                        $reward = bcmul($agency_contest->reward, 0.2);
                        break;
                }
            }

            $agency_contest_user->is_receive_award = 1;
            $agency_contest_user->save();
            // 加余额
            $user->increment('balance', $reward);
            // 流水
            Account::create([
                'type' => Account::AGENCY_CONTEST,
                'to' => $user->id,
                'amount' => $reward,
            ]);


            return true;
        });
        //这里要清楚详情接口的缓存
        Cache::forget('AgencyContestShow:'.$user->id);
        if($res !== true){
            $this->errorResponse(Response::HTTP_OK, transtable($res), $res);
        }
        return $this->success();
    }



}
