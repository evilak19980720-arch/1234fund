<?php

namespace App\Http\Controllers\Api\v1;
/**
 * 参与记录查询
 * */

use App\Http\Requests\Api\v1\CrowdOrderRequest;
use App\Http\Resources\CrowdOrderResource;
use App\Http\Resources\CrowdParticipateResource;
use App\Models\Crowd;
use App\Models\Issue;
use App\Models\CrowdsOrder;
use App\Models\User;
use App\Services\FundingService;
use Carbon\Carbon;
use Illuminate\Http\Response;

class CrowdOrderController extends Controller
{

    /**
     * 本期参与
     * **/
    public function participateIssue(CrowdOrderRequest $request){
        return $this->success([]);
//        $user = $request->user();
//        $issue = Issue::where('crowd_id','=',$request->crowd_id)
//            ->where('issue','=',$request->issue)->first();
//        if(!$issue){
//            $this->errorResponse(Response::HTTP_OK, trans('error.400'), 400);
//        }
//        $orders = CrowdsOrder::select('user_id','portion','amount','created_at')
//            ->with([
//                'user' => function ($query) {
//                    $query->select('id', 'name', 'avatar','hidephone','hierarchy_id');
//                }
//            ])
//            ->where('crowd_id','=',$request->crowd_id)
//            ->where('issue','=',$request->issue)
//            ->get();
//        foreach ($orders as $val){
//            $val->user = User::hideName($val->user,$user);
//        }
//        return $this->success(CrowdParticipateResource::collection($orders));
    }

    /**
     * 我的参与记录
     * **/
    public function participateUser(CrowdOrderRequest $request){
        return $this->success([]);
//        $user = $request->user();
//        $page = $request->get('page', 1);
//        $limit = $request->get('limit', 10);
//        $page = $this->paging($page, $limit);
//
//        $orders = CrowdsOrder::select('issue','crowd_id','portion','win','status')
//            ->where('user_id','=',$user->id)
//            ->offset($page)->limit($limit)
//            ->orderBy('created_at','desc')
//            ->get();
//        foreach ($orders as &$val){
//            $issue = Issue::select('portion','total_price','bought','draw','end_time')->where('crowd_id','=',$val->crowd_id)
//                ->where('issue','=',$val->issue)->first();
//            $crowd = Crowd::with([
//                    'pet' => function ($query) {
//                        $query->select('id', 'name', 'image');
//                    }
//                ])
//                ->where('id', '=', $val->crowd_id)
//                ->select('id', 'pet_id','rate')
//                ->orderBy('id','desc')
//                ->first();
//            $val->issues = $issue;
//            $val->crowd = $crowd;
//        }
//        return $this->success(CrowdOrderResource::collection($orders));
    }

    /**
     * 往期揭晓
     * **/
    public function pastOrder(CrowdOrderRequest $request){
        return $this->success([]);
//        $page = $request->get('page', 1);
//        $limit = $request->get('limit', 10);
//        $page = $this->paging($page, $limit);
//        $user = $request->user();
//
//        $issues = Issue::select('issue','crowd_id','start_time','total_price','portion')
//            ->where('crowd_id','=',$request->crowd_id)
//            ->where('draw','=','1')
//            ->where('end_time','<',Carbon::now()) // 只查时间已到的
//            ->orderBy('start_time','desc')
//            ->offset($page)->limit($limit)
//            ->get();
//
//        foreach ($issues as &$val){
//            $win_user = CrowdsOrder::select('user_id','portion')
//                ->with([
//                    'user' => function ($query) {
//                        $query->select('id', 'name', 'avatar','hidephone','hierarchy_id');
//                    }
//                ])
//                ->where('win','=',1)
//                ->where('crowd_id','=',$val->crowd_id)
//                ->where('issue','=',$val->issue)
//                ->first();
//
//            $win_user->amount = $val->total_price / $val->portion * $win_user->portion;
//
//            $win_user->user = User::hideName($win_user->user,$user);
//
//            $val->win_user = $win_user;
//            $val->start_time = Carbon::parse($val->start_time)->format('d-m-Y H:i');
//        }
//        return $this->success($issues);
    }





}
