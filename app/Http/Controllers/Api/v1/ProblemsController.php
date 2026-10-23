<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Problem;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Requests\Api\v1\ProblemRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Response;

/**
 * 问题
 * @package App\Http\Controllers\Api\v1
 */
class ProblemsController extends Controller
{

    public function problems(Request $request)
    {
        $lists = Problem::select('title')->where('hot','=',1)->orderBy('hits','desc')->get()->toArray();
        $lists[] = ["title"=>"Other questions"];
        return $this->success($lists);
    }

    public function reply(Request $request)
    {
        return $this->success();
        $user = auth('api')->user();
        $customer = app(Customer::class)->userCustomer($user);
        if (!$customer) {
            $this->errorResponse(Response::HTTP_OK, transtable(10020), 10020);
        }
        return $this->success($customer ? new CustomerResource($customer) : $customer);
    }
//    public function reply(ProblemRequest $request,Customer $customerModel)
//    {
//        $user = $user = $request->user();
//        $title = $request->title;
//        $type = Problem::DEFAULT_REPLAY;
//        $servers['show'] = 0;  //0默认不显示  1倒计时  2显示客服
//        $servers['countdown'] = 0;
//        $servers['text'] = "";
//        //直接匹配
//        $reply = Problem::select('id','reply','withdraw')->where('title','=',$title)->first();
//        //模糊匹配
//        if(!$reply){
//            $all = Problem::select('id','title','keyword','withdraw','reply')->orderBy('hits','desc')->get();
//
//            foreach ($all as $problem){
//                if(Str::containsAll(Str::of($title)->lower(),explode(',',Str::of($problem->keyword)->lower()))){
//                    $reply = $problem;
//                    continue;
//                }
//            }
//        }
//
//        $customer = $customerModel->userCustomer($user);
//
//        if($reply){
//            Problem::where('id','=',$reply->id)->increment('hits',1);
//            //验证提现
//            if($reply->withdraw == 1){
//                $type = Problem::WITHDRAW;
//                $servers = self::reply_withdraw($user,$servers);
//                if (!$customer && $servers['show'] == 0)
//                {
//                    $this->errorResponse(Response::HTTP_OK, trans('error.' . 10020), 10020);
//                }
//
//            }
//        }else{
//            //联系客服
//            $type =Problem::SERVERS;
//            $servers['show'] = 2;
//            if (!$customer)
//            {
//                $this->errorResponse(Response::HTTP_OK, trans('error.' . 10020), 10020);
//            }
//        }
//
//        $servers['nowtime'] = Carbon::now()->timestamp;
//
//        $servers['customers'] = $customer ? new CustomerResource($customer) : $customer;
////        $servers['customers'] = new CustomerResource(Customer::getCustomer(0, $user->id)[0]);
//        $re[] = [
//            'type' => $type,
//            'reply' => empty($reply)?"":$reply->reply,
//            'servers' => $servers
//        ];
//        return $this->success($re);
//    }

    private function reply_withdraw($user,$servers){
        //如果是关于提现的  后台后面会跟一个 ：联系人工客服的按钮，然后后端判断这个客户最早一笔提交的提现申请是否超过了48小时，如果超过了48小时 则显示人工客服且可以点击跳转 如果没有超过，就倒计时显示：你还有xxx小时才能联系人工客服
        $order = Order::where('user_id','=',$user->id)->where('type','=',0)->where('closed','=',0)->where('state','=',-1)->orderBy('created_at','asc')->first();
        if($order){
            $h = Carbon::now()->diffInHours(Carbon::parse($order->created_at), true);
            $servers['show'] = $h>=48?2:1;
            $servers['countdown'] = Carbon::parse($order->created_at->addHours(48))->timestamp;
            $servers['text'] = sprintf(trans('error.' . 10019),48-$h);
        }else{
            $servers['show'] = 0;
        }
        return $servers;
    }


}
