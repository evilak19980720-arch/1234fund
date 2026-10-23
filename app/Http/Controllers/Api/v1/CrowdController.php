<?php

namespace App\Http\Controllers\Api\v1;


use App\Http\Requests\Api\v1\CrowdRequest;
use App\Http\Requests\Api\v1\CrowdShowRequest;
use App\Http\Resources\CrowdResource;
use App\Models\Agent;
use App\Models\Crowd;
use App\Models\CrowdsOrder;
use App\Models\Issue;
use App\Models\User;
use App\Services\FundingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CrowdController extends Controller
{
    public function index(CrowdRequest $request)
    {
        return $this->success([]);
//        $user = $request->user();
//        $agent = Agent::getAgent($user->id);
//
//        $page = $request->get('page', 1);
//        $limit = $request->get('limit', 100);
//        $page = $this->paging($page, $limit);
//
//        $now = Carbon::now();
//        $crowds = Crowd::with([
//            'pet' => function ($query) {
//                $query->select('id', 'name', 'image');
//            },
//            'issues' => function ($query) use ($now) {
//                $query->select('id', 'crowd_id', 'issue', 'portion', 'total_price', 'bought', 'start_time', 'end_time')
//                    ->where('start_time', '<=', $now)
//                    ->where('end_time', '>', $now);
//            }
//        ])->where('crowds.state', '=', 1)
//            ->select('crowds.id', 'crowds.pet_id')
//            ->join('pets','crowds.pet_id','=','pets.id')
//            ->where('pets.admin_user_id', $agent)
//            ->offset($page)->limit($limit)
//            ->orderBy('crowds.sort', 'desc')
//            ->get();
//
//
//        $crowds = $crowds->toArray();
//        $relist = [];
//        foreach ($crowds as $key=>$val){
//            unset($val['pet_id']);
//
//            if (!$val['issues']) {
//                $issues = Issue::select('id', 'crowd_id', 'issue', 'portion', 'total_price', 'bought', 'start_time', 'end_time')
//                    ->where('start_time', '>=', Carbon::now())
//                    ->where('crowd_id', '=', $val['id'])
//                    ->orderBy('start_time','asc')
//                    ->limit(1)
//                    ->get();
//                if($issues){
//                    $val['issues'] = $issues->toArray();
//                }
//            }
//
//            $val['pet']['image'] = sprintf('%s/%s', aliyun_domain(), $val['pet']['image']);
//            if ($val['issues']) {
//                $val['issue'] = $val['issues'][0];
//                unset($val['issues']);
//                unset($val['issue']['crowd_id']);
//                $val['issue']['start_time'] = Carbon::parse($val['issue']['end_time'])->timestamp;
//                $val['issue']['end_time'] = Carbon::parse($val['issue']['end_time'])->timestamp;
//                $val['now'] = Carbon::now()->timestamp;
//                $relist[] = $val;
//            }
//        }
//        return $this->success($relist);

    }

    public function show(CrowdShowRequest $request)
    {
        return $this->success([]);

//        $crowd = Crowd::find($request->id);
//        $user = $request->user();
//        $now = Carbon::now();
//
//        $pet = $crowd->pet()->select('id', 'name', 'image')->first();
//        $pet->image = sprintf('%s/%s', aliyun_domain(), $pet->image);
//
//        if ($request->has('issue')) {
//            $issue = $crowd->issues()
//                ->select('id', 'issue', 'start_time', 'end_time', 'draw', 'portion', 'total_price', 'profit', 'day', 'bought')
//                ->where('issue',$request->issue)
//                ->first();
//        } else {
//            $issue = Issue::select('id', 'issue', 'start_time', 'end_time', 'draw', 'portion', 'total_price', 'profit', 'day', 'bought')
//                ->where('crowd_id','=',$request->id)
//                //->where('start_time', '<=', $now)
//                ->where('end_time', '>', $now)
//                ->orderBy('end_time','asc')
//                ->first();
//        }
//
//        if(!$issue){
//            $this->errorResponse(Response::HTTP_OK, trans('error.404'), 404);
//        }
//
//        //Log::error('id:'.$request->id.'|issue:'.$request->issue);
//        $issue->start_time = Carbon::parse($issue->start_time)->timestamp;
//        $issue->end_time = Carbon::parse($issue->end_time)->timestamp;
//
//        //查中奖人
//        $crowdsOrder = (object)[];
//        if($issue->draw == 1){
//            $crowdsOrder = CrowdsOrder::select('user_id','portion','amount')
//                ->with([
//                    'user' => function ($query) {
//                        $query->select('id','name', 'avatar','hidephone','hierarchy_id');
//                    }
//                ])
//                ->where('crowd_id','=',$request->id)->where('issue','=',$issue->issue)
//                ->where('win','=',1)
//                ->first();
//            if($crowdsOrder){
//                $crowdsOrder->user = User::hideName($crowdsOrder->user,$user);
//            }
//        }
//
//        //详情 时间到 再返回开奖
//        if ($issue->end_time > time()) {
//            $issue->draw = 0;
//        }
//
//        return $this->success([
//            'id'    => $crowd->id,
//            'desc'  => $crowd->desc ?: '',
//            'now'   => $now->timestamp,
//            'issue' => $issue,
//            'pet'   => $pet,
//            'crowdsOrder' => $crowdsOrder
//        ]);
    }

    public function store(CrowdRequest $request)
    {
        return $this->success([]);
//        $this->errorResponse(Response::HTTP_OK, trans('error.403'), 403);
//        $user = $request->user();
//        $now = Carbon::now();
//
//        $issue = Issue::where('issue', $request->issue)->where('crowd_id',$request->crowd_id)->first();
//        if (!$issue) {
//            $this->errorResponse(Response::HTTP_OK, trans('error.404'), 404);
//        }
//
//        if ($now->lt($issue->start_time)) {
//            $this->errorResponse(Response::HTTP_OK, trans('error.90003'), 90003);
//        }
//
//        if ($now->gt($issue->end_time)) {
//            $this->errorResponse(Response::HTTP_OK, trans('error.90004'), 90004);
//        }
//
//        if ($issue->draw == 1) {  //已开奖返回售罄
//            //$this->errorResponse(Response::HTTP_OK, trans('error.90002'), 90002);
//            $this->errorResponse(Response::HTTP_OK, sprintf(trans('error.90001'), $issue->portion - $issue->bought), 90001);
//        }
//
//        if ($request->quantity + $issue->bought > $issue->portion) {
//            $this->errorResponse(Response::HTTP_OK, sprintf(trans('error.90001'), $issue->portion - $issue->bought), 90001);
//        }
//
//        $service = new FundingService($issue);
//        if ($service->unitPrice() * $request->quantity > $user->balance) {
//            $this->errorResponse(Response::HTTP_OK, trans('error.10009'), 10009);
//        }
//
//        if ($service->buy($user->id,$request->quantity)) {
//            return $this->success();
//        } else {
//            $this->errorResponse(Response::HTTP_OK, trans('error.90005'), 90005);
//        }
    }

}
