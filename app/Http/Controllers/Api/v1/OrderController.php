<?php

namespace App\Http\Controllers\Api\v1;

use App\Handlers\KeysHandler;
use App\Http\Requests\Api\v1\OrderRequest;
use App\Http\Requests\Api\v1\WithdrawalRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * 订单
 * @package App\Http\Controllers\Api\v1
 */
class OrderController extends Controller
{
    public function index(OrderRequest $request)
    {
        $user = $request->user();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $page = $this->paging($page, $limit);
        $type = Order::ORDER_TYPE[$request->type];
        $state = $request->state;

        $key = sprintf(KeysHandler::ORDER_LIST,$user->id,$type,$page,$limit,$state);
        $orders = Cache::get($key);
        if (!$orders) {
            $orders_query = Order::where('user_id', '=', $user->id)->where('type','=',$type)->where('show','=',Order::SHOW);
            switch ($state){
                case -2:
                    break;
                case -1:
                    $orders_query->whereIn('state',[-1,0]);
                    break;
                case 1:
                    $orders_query->where('state','=',1);
                    break;
                case 2:
                    $orders_query->whereIn('state',[2,3,4]);
                    break;
            }

            $orders = $orders_query->select('id', 'no', 'amount','withdrawal_fee', 'handlingfee','actual_amount', 'remark', 'state', 'created_at')
                ->offset($page)->limit($limit)->orderBy('id','desc')->get();
            Cache::put($key,$orders,2);
        }

        return $this->success(OrderResource::collection($orders));
    }

    //取消提现
    public function withdrawal_cancel(OrderRequest $request){
        $user = $request->user();
        $order = Order::where('id','=',$request->order_id)->where('user_id','=',$user->id)->where('state','=','-1')->where('type','=',0)->first();
        if(!$order){
            $this->errorResponse(Response::HTTP_OK, transtable('40008'), 40008);
        }
        $re = Order::withdrawCancel($request->order_id);
        if($re){
            return $this->success();
        }
        $this->errorResponse(Response::HTTP_OK, transtable('40009'), 40009);
    }


    public function event(Request $request){
        //$user = $request->user();
        $no = $request->no;
        $data = DB::transaction(function () use ($no) {
            $order = Order::where('no','=',$no)->select('id','isevent','no','amount')->lockForUpdate()->first();
            if(!$order){
                return $this->errorResponse(Response::HTTP_OK, transtable('40008'), 40008);
            }
            $data['isevent'] = $order->isevent;
            $data['no'] = $order->no;
            $data['amount'] = $order->amount;
            $order->isevent = 1;
            $order->save();
            return $data;
        });
        return $this->success($data);
    }


    /**
     * 假的提现列表
     * @param WithdrawalRequest $request
     * @param Order $order
     * @return JsonResponse|object
     * /withdraw/record/v2?f=10&m=100&s=1000&l=8
     */
    public function indexcopy(Request $request, Order $order)
    {
        //f=5&m=100&s=1000
        $f = $request->get('f', 5);
        $m = $request->get('m', 100);
        $s = $request->get('s', 1000);
        $l = $request->get('l', 5);
        $page = $request->get('page');
        if($page > 1){
            return $this->success([]);
        }
        $limit = rand(1,10);
        $limit = $l;
        $time_arr = [];
        for($i = 0;$i<=$limit+1;$i++){
            $time_arr[] = time() - $i *24*60*60-rand(1,5)*58*59;
        }
        $list = $order
            //->where('type', '=', Order::WITHDRAWAL)
            ->select(['id', 'no', 'amount','withdrawal_fee', 'handlingfee','actual_amount', 'remark', 'state', 'created_at'])
            ->inRandomOrder()
            ->limit($limit)->get();
        foreach ($list as $key=>&$val){
            $val->no = only_code();
            $val->withdrawal_fee = $f * 100;
            $val->amount = sprintf("%.2f",rand($m,$s)) * 100;
            $val->created_at = date('Y-m-d H:i:s',$time_arr[$key]);
            $val->state = 1;
            $val->remark = "";
        }
        return $this->success(OrderResource::collection($list));

    }


}
