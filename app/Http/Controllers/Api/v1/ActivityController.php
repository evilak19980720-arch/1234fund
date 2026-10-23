<?php

namespace App\Http\Controllers\Api\v1;

use App\Handlers\KeysHandler;
use App\Http\Requests\Api\v1\ActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\ActivitiesUser;
use App\Models\Activity;
use App\Models\ActivityUser;
use App\Models\Card;
use App\Models\Configuration;
use App\Models\Pet;
use App\Models\PetOrder;
use App\Models\TaskLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * 活动
 * @package App\Http\Controllers\Api\v1
 */
class ActivityController extends Controller
{
    public function index(ActivityRequest $request)
    {
        $user = $request->user();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $page = $this->paging($page, $limit);

        $key = sprintf(KeysHandler::ACTIVITIES_PAGE, $page, $limit);
        $activities = Cache::get($key);
        if (!$activities) {
            $activities = Activity::query()
                ->where('admin_user_id', '=', $user->admin_user_id)
                ->where('status', '=', Activity::STATUS_ON)
                ->select(['id', 'name', 'amount', 'type', 'description', 'number', 'num', 'pet_id', 'subordinate_buy_number', 'condition','created_at'])
                ->offset($page)->limit($limit)->orderBy('sort', 'desc')
                ->orderBy('created_at', 'desc')->get();
            if ($activities) {
                foreach ($activities as $key => $activity) {
                    switch ($activity->type) {
                        //邀请好友
                        case Activity::DOWN:
                            $completed = $user->method == 'default' ? false : true;
                            $activities[$key]->completed = $completed;
                            $activities[$key]->completed_number = $completed ? 1 : 0;
                            break;
                        //邀请好友并下载
                        case Activity::INVITE_FRIENDS_AND_DOWNAPP:
                            $number = $user->sub_first_downapp;
                            $completed = $number >= $activity->number;
                            $activities[$key]->completed = $completed;
                            $activities[$key]->completed_number = $number;
                            break;
                        case Activity::INVITE_FRIENDS:
                            $number = $user->child(1)->count();//一级下级人数
                            $completed = $number >= $activity->number;
                            $activities[$key]->completed = $completed;
                            $activities[$key]->completed_number = $number >= $activity->number ? $activity->number : $number;
                            break;
                        //邀请好友并且购买&&自己购买任意
                        case Activity::INVITE_FRIENDS_AND_SUBORDINATE_GRAB:
                            $child = app(Activity::class)->invite($user, $activity);
                            $number = $child->count();
                            //判断是否需要满足宠物要求
                            $pet_order = 0;
                            $activities[$key]->description = $number >= $activity->number ? $activity->description : $activity->name;
                            if ($number >= $activity->number && $activity->pet_id) {
                                $pet_order = PetOrder::query()->whereIn('user_id', $child->pluck('id'))
                                    ->where('pet_id', '=', $activity->pet_id)
                                    ->whereIn('type', [1, 3])->groupBy('user_id')->get()->count();
                            }
                            //新增自己购买任意
                            $my_order = 0;
                            if($pet_order >= $activity->subordinate_buy_number){
                                $my_order = PetOrder::query()->where('user_id', $user->id)
                                    ->whereIn('type', [1, 3])->groupBy('user_id')->get()->count();
                            }

                            $activities[$key]->completed = $number >= $activity->number && $pet_order >= $activity->subordinate_buy_number && $my_order;
                            $activities[$key]->completed_number = $number >= $activity->number ? $activity->number : $number;
                            break;
                        //邀请好友并绑卡
                        case Activity::INVITE_FRIENDS_AND_CARD:
                            //info($activity->id);
                            //info($activity->created_at);
                            /*
                            $number = User::query()
                                ->where('relation', 'regexp', sprintf(',%d,%s$', $user->id, str_repeat('[0-9]+,', 0)))
                                ->where('created_at','>',Carbon::parse($activity->created_at))
                                ->has('card')
                                ->get()->count();//一级下级人数
                            */
                            $number = $user->sub_first_bindcard;
                            $completed = $number >= $activity->number;
                            $activities[$key]->completed = $completed;
                            $activities[$key]->completed_number = $number;
                            //$activities[$key]->completed_number = $number >= $activity->number ? $activity->number : $number;

                            break;
                        //邀请好友并购买
                        case Activity::INVITE_FRIENDS_AND_SUBPETORDER:
                            $number = $user->sub_first_petorder;
                            $completed = $number >= $activity->number;
                            $activities[$key]->completed = $completed;
                            $activities[$key]->completed_number = $number;
                            break;
                        //邀请好友并充值
                        case Activity::INVITE_FRIENDS_AND_RECHARGE:
                            $number = User::query()
                                ->where('relation', 'regexp', sprintf(',%d,%s$', $user->id, str_repeat('[0-9]+,', 0)))
                                ->where('created_at','>',Carbon::parse($activity->created_at))
                                ->whereHas('orders', function ($query) {
                                    $query->where('type','=',1)->where('state','=',1);
                                })
                                ->get()->count();//一级下级人数
                            $completed = $number >= $activity->number;
                            $activities[$key]->completed = $completed;
                            $activities[$key]->completed_number = $number >= $activity->number ? $activity->number : $number;

                            break;
                        //签到
                        case Activity::SIGN:
                            $activities[$key]->completed = true;
                            $activities[$key]->received = app(Activity::class)->receivedSignReward($user, $activity->id);
                            $activities[$key]->completed_number = intval($activities[$key]->received);
                            break;

                        default:
                            $activities[$key]->completed = false;
                    }
                    $activities[$key]->keyword = 'activity';
                }
            }
            /*
            //把签到活动追加进去
            if (Configuration::getItem('sign_in') && Configuration::getItem('sign_in_reward') && Configuration::getItem('sign_in_subordinate')) {
                $completed = $user->countSubordinate(1) >= Configuration::getItem('sign_in_subordinate');
                $append_activity = [
                    'id' => rand(1000, 9999),
                    'name' => 'sign in',
                    'keyword' => 'sign',
                    'amount' => (int)Configuration::getItem('sign_in_reward'),
                    'description' => 'With ' . Configuration::getItem('sign_in_subordinate') . ' subordinates, you can start the sign-in task, and sign-in rewards ' . bcdiv(Configuration::getItem('sign_in_reward'), 100) . 'rs',
                    'number' => 1,
                    'completed' => $completed,
                    'completed_number' => $completed ? 1 : 0,
                    'received' => app(Activity::class)->receivedSignReward($user),
                ];
                $activities->prepend($append_activity);
            }
            */
            $activities && Cache::put($key, $activities, 60);
        }

        return $this->success(ActivityResource::collection($activities));
    }

    public function store(ActivityRequest $request)
    {
        $user = $request->user();
        $activity = Activity::getActivity($request->id);
        if (!$activity) {
            $this->errorResponse(Response::HTTP_OK, transtable('70000'), 70000);
        }
        if ($activity->status == 0) {
            $this->errorResponse(Response::HTTP_OK, transtable('70001'), 70001);
        }
        if ($activity->number <= 0) {
            $this->errorResponse(Response::HTTP_OK, transtable('70006'), 70006);
        }

        switch ($activity->type) {
            case Activity::DOWN:
                $completed = $user->method == 'default' ? false : true;
                if (!$completed) {
                    $this->errorResponse(Response::HTTP_OK, transtable('70003'), 70003);
                }
                break;
            case Activity::INVITE_FRIENDS:
                $number = $user->child(1)->count();//一级下级人数
                $completed = $number >= $activity->number;
                if (!$completed) {
                    $this->errorResponse(Response::HTTP_OK, transtable('70003'), 70003);
                }
                break;
            case Activity::INVITE_FRIENDS_AND_SUBORDINATE_GRAB:
                $child = app(Activity::class)->invite($user, $activity);
                $number = $child->count();
                //判断是否需要满足宠物要求
                $pet_order = 0;
                if ($number >= $activity->number && $activity->pet_id) {
                    $pet_order = PetOrder::query()->whereIn('user_id', $child->pluck('id'))
                        ->where('pet_id', '=', $activity->pet_id)
                        ->whereIn('type', [1, 3])->groupBy('user_id')->get()->count();
                }
                //新增自己购买任意
                $my_order = 0;
                if($pet_order >= $activity->subordinate_buy_number){
                    $my_order = PetOrder::query()->where('user_id', $user->id)
                        ->whereIn('type', [1, 3])->groupBy('user_id')->get()->count();
                }
                $completed = $number >= $activity->number && $pet_order >= $activity->subordinate_buy_number && $my_order;
                if (!$completed) {
                    $this->errorResponse(Response::HTTP_OK, transtable('70003'), 70003);
                }
                break;

            //邀请好友并绑卡
            case Activity::INVITE_FRIENDS_AND_CARD:
                $number = $user->sub_first_bindcard;//一级下级人数
                $completed = $number >= $activity->number;
                if (!$completed) {
                    $this->errorResponse(Response::HTTP_OK, transtable('70003'), 70003);
                }

                //增加签到人数判断
            /*
                $sign_num = ActivityUser::query()
                    ->whereHas('activites',function($sql) {
                        $sql->where('type', '=', Activity::SIGN);
                    })
                    ->whereIn('user_id', function ($query) use ($user) {
                        $query->select('id')->from('users')->where('parent_id', '=', $user->id);
                    })
                    ->groupBy('user_id')
                    ->get()
                    ->count();

                $this_activity_number = $activity->number;
             //   if($sign_num < $this_activity_number){
             //       $this->errorResponse(Response::HTTP_OK, transtable('70003'), 70003);
             //   }


                //查是否完成过这个任务
                $sucess_num =  Activity::query()
                    ->where('type','=',Activity::INVITE_FRIENDS_AND_CARD)
                    ->whereHas('users',function($sql) use ($user) {
                        $sql->where('user_id','=',$user->id);
                    })
                    ->sum('number');
               // if($sign_num < $this_activity_number + $sucess_num){
               //     $this->errorResponse(Response::HTTP_OK, transtable('70003'), 70004);
               // }

              //  info('会员ID:'.$user->id.'签到人数:'.$sign_num.'当前活动需要下级人数:'.$this_activity_number.'以前领取该活动的下级人数:'.$sucess_num);

*/

                break;
            //邀请好友并购买
            case Activity::INVITE_FRIENDS_AND_SUBPETORDER:
                $number = $user->sub_first_petorder;//一级下级人数
                $completed = $number >= $activity->number;
                if (!$completed) {
                    $this->errorResponse(Response::HTTP_OK, transtable('70003'), 70003);
                }

                break;
            //邀请好友并下载APP
            case Activity::INVITE_FRIENDS_AND_DOWNAPP:
                $number = $user->sub_first_downapp;//一级下级人数
                $completed = $number >= $activity->number;
                if (!$completed) {
                    $this->errorResponse(Response::HTTP_OK, transtable('70003'), 70003);
                }

                break;
            //邀请好友并充值
            case Activity::INVITE_FRIENDS_AND_RECHARGE:
                $number = User::query()
                    ->where('relation', 'regexp', sprintf(',%d,%s$', $user->id, str_repeat('[0-9]+,', 0)))
                    ->where('created_at','>',Carbon::parse($activity->created_at))
                    ->whereHas('orders', function ($query) {
                        $query->where('type','=',1)->where('state','=',1);
                    })
                    ->get()->count();//一级下级人数
                $completed = $number >= $activity->number;
                if (!$completed) {
                    $this->errorResponse(Response::HTTP_OK, transtable('70003'), 70003);
                }
                break;
        }

        try {
            ///增加VIP判断
            $vips_arr = explode(',',$activity->vips);
            if(!in_array($user->vip_id , $vips_arr)){
                return $this->fail(70007, transtable(70007));
            }
            $res = (new ActivityUser())->receive($user, $activity);
            if ($res == 1) {
                return $this->success();
            }
            return $this->fail($res, transtable($res));
        } catch (\Exception $exception) {
            info('活动奖励领取异常', ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
            return $this->fail(70006, transtable('70006'));
        }
    }



}
