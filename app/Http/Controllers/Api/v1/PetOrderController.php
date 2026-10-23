<?php


namespace App\Http\Controllers\Api\v1;


use App\Events\BuyPetSuccessEvent;
use App\Http\Requests\Api\v1\PetOrderCancelRequest;
use App\Http\Requests\Api\v1\PetOrderRequest;
use App\Http\Requests\Api\v1\PetOrderSellRequest;
use App\Http\Requests\Api\v1\PetTransferRequest;
use App\Http\Resources\PetOrderResource;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\FundOrder;
use App\Models\Order;
use App\Models\Pet;
use App\Models\PetOrderTransfer;
use App\Models\PetActivity;
use App\Models\PetOrder;
use App\Models\Rule;
use App\Models\User;
use App\Models\UserEvent;
use App\Models\UserStatistic;
use App\Models\Vip;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PetOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $page = $this->paging($page, $limit);
        $q = PetOrder::query()
            ->with('pet')
            ->offset($page)
            ->limit($limit);
        $q->where('type','=',1);
        $q->where('user_id','=',$user->id);
        if($request->type == 1){
            $q->where('state','=',0);
        }elseif ($request->type == 3){
            $q->where('state','=',1);
        }
        $q->orderBy('id', 'desc');
        //$pet_order = $q->get(['id', 'price', 'amount', 'no', 'day', 'type', 'state', 'sell_day', 'created_at','selling_amount','start_at','form_user_id','discount','number']);
        $pet_order = $q->get(['id', 'pet_id', 'price', 'number', 'amount', 'no', 'profit_amount', 'day', 'type', 'state', 'cancelled', 'start_at', 'created_at','selling_amount','rate_of_return','sell_day']);


        if(!$pet_order->isEmpty()){
            $user_vip = Vip::getVip($user->vip_id);
            foreach ($pet_order as &$order){
                if($user->discount_switch == 1){
                    $order->amount = bcmul($order->amount, $user_vip->discount);
                    $order->price = bcmul($order->price, $user_vip->discount);
                    $order->can_transfer = 0;
                    if(empty($order->form_user_id)){
                        $order->can_transfer = 1;
                    }
                }
            }
        }


        return $this->success(PetOrderResource::collection($pet_order));
    }

    public function sell(PetOrderSellRequest $request)
    {

        //关闭卖出功能
        $this->errorResponse(Response::HTTP_OK, transtable( 80006), 80006);

        $user = $request->user();
        $pet_order = PetOrder::where(['id' => $request->pet_order_id, 'type' => 1])
            ->where('user_id', '=', $user->id)
            ->where('state', '=', 0)
            //->whereRaw('datediff(curdate() ,`start_at`)>=`day`')
            ->first();

        if (!$pet_order) {
            $this->errorResponse(Response::HTTP_OK, transtable(80006), 80006);
        }

        //时间判断改为小时
        $carbon_start_at = Carbon::parse($pet_order->start_at);
        if (Carbon::now()->diffInHours($carbon_start_at, true) < $pet_order->day * 24 + 24) {
            $this->errorResponse(Response::HTTP_OK, transtable(80007), 80007);
        }

        $pet = Pet::getPet($pet_order->pet_id);
        //判断系统是否开启必须持有才能卖出
        //20210127修改 比如他要卖300的鱼 必须现在买一条500的  然后才可以卖出300的
        /*        if ($pet->next_pet_id != 0) {
                    $next_pet_order = PetOrder::where('id', '>', $pet_order->id)
                        ->where('user_id', '=', $user->id)
                        ->where('state', '=', 0)
                        ->where('pet_id', '=', $pet->next_pet_id)
                        ->first();
                    if (!$next_pet_order) {
                        $next_pet = Pet::getPet($pet->next_pet_id);
                        $this->errorResponse(Response::HTTP_OK, sprintf(transtable(80005), $next_pet->buying_price/100), 80005);
                    }
                }*/

        try {
            //卖出宠物
            $res = app(PetOrder::class)->sell($user, $pet_order, $pet);
            if ($res != 1) {
                $this->errorResponse(Response::HTTP_OK, transtable($res), $res);
            }
            return $this->success();
        } catch (\Exception $exception) {
            $code = in_array($exception->getCode(), [80004]) ? $exception->getCode() : 80010;
            $this->errorResponse(Response::HTTP_OK, transtable($code), $code);
        }

    }

    public function buy(PetOrderRequest $request, Account $account)
    {
        try {
        //限制300的鱼用户只能买一次
        $user = $request->user();
        //if ($request->pet_id == 85 && PetOrder::where('user_id', '=', $user->id)->where('pet_id', '=', 85)->where('cancelled', '=', 0)->count() != 0) {
        //    $this->errorResponse(Response::HTTP_OK, transtable(80008), 80008);
        //}

        if (!$request->has('number')) {
            $request->number = 1;
        }

        //用户当前持有过的数量
        $pet_order_last_count = PetOrder::where('user_id', '=', $user->id)->where('type',1)->where('cancelled', '=', 0)->count();

        $res = DB::transaction(function () use ($request, $account) {
            $user = User::where('id', $request->user()->id)->first();
            $pet = Pet::where('id', $request->pet_id)->sharedLock()->first();
            $time = time();
            $act = PetActivity::query()
                ->where(['state' => 1, 'pet_id' => $request->pet_id])
                ->where('start_at', '<=', $time)
                ->where('end_at', '>=', $time)
                ->first();

            if (!$pet) {
                return 80000;
            }

            $buyingPrice = $pet->buying_price;
            $sellingPrice = $pet->selling_price;
            $profit = $pet->profit;
            $day = $pet->day;
            $rate_of_return = $pet->rate_of_return;


            if ($act) {
                $buyingPrice = $act->act_buying_price;
                $sellingPrice = $act->act_selling_price;
                $profit = $act->act_profit;
                $day = $act->act_day;
                $rate_of_return = $act->act_rate_of_return;
            }

            $user_vip = Vip::getVip($user->vip_id);
            if($user->discount_switch == 1){
                $amount = bcmul($request->number * $buyingPrice, $user_vip->discount);
            }else{
                $amount = $request->number * $buyingPrice;
            }

            if ($user->balance < $amount) {
                return 10009;
            }
            /* 取消判断数量*/
            if ($pet->stock_quantity < $request->number && $request->type == 1) {
                return 80000;
            }


            if(Configuration::getItem('charge_buy_pet_switch') == 1
                &&
                $user->hierarchy_id == 1
                &&
                Order::query()
                    ->where('type', '=', Order::RECHARGE)
                    ->where('user_id', '=', $user->id)
                    ->where('state','=',1)
                    ->count() == 0){
                return 80011;
            }

            $holding_quantity = PetOrder::where('user_id', '=', $user->id)->where('pet_id', '=', $pet->id)->whereIn('type', [1, 2])->where('state', '=', 0)->where('cancelled', '=', 0)->sum('number');
            if ($holding_quantity + $request->number > $pet->holding_quantity) {
                return 80001;
            }

            /*
            $data = [
                'user_id' => $user->id,
                'pet_id' => $request->pet_id,
                'price' => $buyingPrice,
                'number' => $request->number,
                'amount' => $request->number * $buyingPrice,
                //'profit_amount' => $profit * $request->number,
                'selling_price' => $sellingPrice,
                'selling_amount' => $sellingPrice * $request->number,
                'day' => $day,
                'type' => $request->type,
                'state' => 0,
                //'rate_of_return' =>$rate_of_return,
                'no' => only_code()
            ];

            //不开折扣
            if($user->discount_switch == 0){
                $data['start_at'] = Carbon::now();
            }else{
                $data['discount'] = $user_vip->discount;
            }

            //创建订单
            $pet_order = PetOrder::create($data);
            */

            //订单修改为生成多订单
            $data = [
                'user_id' => $user->id,
                'pet_id' => $request->pet_id,
                'price' => $buyingPrice,
                'amount' => $buyingPrice,
                'profit_amount' => $profit,
                'selling_price' => $sellingPrice,
                'selling_amount' => $sellingPrice,
                'day' => $day,
                'type' => $request->type,
                'state' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'rate_of_return' =>$rate_of_return,
                'no' => only_code()
            ];
            //不开折扣
            if($user->discount_switch == 0){
                $data['start_at'] = Carbon::now();
            }else{
                $data['discount'] = $user_vip->discount;
            }
            $insert_data = [];
            for ($i=1;$i<= $request->number; $i++){
                $data['no'] = only_code();
                $insert_data[] = $data;
            }
            PetOrder::insert($insert_data);

            //如果是购买则减库存 预约不减库存
            //if ($request->type == 1) {
            $pet->decrement('stock_quantity', $request->number); //减库存
            $account_type = Account::BUY_FISH;
            //} else {
            //    $account_type = Account::PRE_FISH;
            //}
            //减用户余额
            User::where('id', $user->id)->decrement('balance', $amount);
            //添加流水记录
            Account::create([
                'type' => $account_type,
                //'order_id' => $pet_order->id,
                'from' => $user->id,
                'amount' => -$amount
            ]);


            //判断类型为购买的才进行分成，预约订单不分成
            //if ($request->type == 1) {
            //$account->buyCommission($user, $amount);
            //}

            if($user_vip -> level == 1){
                //3.大于VIP1的投资商品，不给上级返佣
                // 20240901 总投资金额 大于总充值金额就 不返给上级
                //总充值
                $total_recharge = Order::getUserTotalRecharge($user->id);
                //总投资
                $total_petorder = PetOrder::query()
                    ->where('user_id','=',$user->id)
                    ->where('type','=',1)
                    //->whereDate('created_at','>',Carbon::parse('2024-09-01'))
                    ->sum('amount');
                if($total_recharge >= $total_petorder){
                    $account->buyCommission($user, $amount);
                    info($user->id.'VIP:'.$user_vip -> level.' 总充值:'.$total_recharge . " 总投资:".$total_petorder ." 正常返佣--总充值:{$total_recharge} 总投资:{$total_petorder}");
                }else{
                    info("{$user->id} VIP:{$user_vip -> level} - 不返佣--总充值:{$total_recharge} 总投资:{$total_petorder}");
                }
            }else{
                info("{$user->id} VIP:{$user_vip -> level} - 不返佣");
            }

            UserEvent::insertEvent($user, UserEvent::PETORDER);

            return 1;
        }, 2);
        if ($res == 1) {

            //第一次投资成功后触发事件，并把这个客户上一次的充值的金额一起上报
            $data['is_first'] = 0;
            $data['last_reamount'] = 0;
            if($pet_order_last_count == 0){
                //查用户最新一次充值金额
                $reorder = Order::query()
                    ->where('type', '=', Order::RECHARGE)
                    ->where('user_id', '=', $user->id)
                    ->where('state','=',1)->latest()->first();
                if($reorder){
                    $data['is_first'] = 1;
                    $data['last_reamount'] = $reorder->amount / 100;
                }
            }
            return $this->success($data);
        }
        $this->errorResponse(Response::HTTP_OK, transtable($res), $res);
        } catch (\Exception $exception) {
            $code = in_array($exception->getCode(), [80008, 80000, 10009, 80001,80011]) ? $exception->getCode() : 80010;
            $this->errorResponse(Response::HTTP_OK, transtable($code), $code);
        }
    }


    /**
     * 转让
     * @param int $fund_order_id     购买记录id
     * @param int $fund_order_id     会员id
     */
    public function transfer(PetTransferRequest $request)
    {
        $user = $request->user();
        $pet_order_id = $request->pet_order_id;

        $phone = $request->phone;
        $to_user = User::where('name', $phone)->first();
        if(!$to_user || empty($to_user)){
            $this->errorResponse(Response::HTTP_OK, transtable(80012), 80012);
        }
        $res = DB::transaction(function () use ($pet_order_id, $user, $to_user) {
            $pet_order = PetOrder::where(['id' => $pet_order_id, 'type' => 1, 'state'=>0 ])
                ->where('user_id', '=', $user->id)
                ->where('state', '=', 0)
                ->lockForUpdate()
                ->first();
            if($pet_order){
                if(!empty($pet_order->form_user_id)){
                    return 80013;
                }
                $form_user_id = $pet_order->user_id;
                $pet_order->form_user_id = $form_user_id;
                $pet_order->user_id = $to_user->id;
                $pet_order->start_at = Carbon::now();
                $pet_order->save();
                /*
                PetOrderTransfer::create([
                    'pet_order_id' => $pet_order_id,
                    'user_id' => $user->id,
                    'to_user_id' => $to_user->id,
                    'transfer_at' => now()
                ]);
                */
                return 1;
            }else{
                return 80006;
            }
        });

        if ($res == 1) {
            return $this->success();
        }
        $this->errorResponse(Response::HTTP_OK, transtable($res), $res);
    }


    //取消预约订单
    public function cancel(PetOrderCancelRequest $request)
    {
        $user = $request->user();
        $pet_order = PetOrder::query()->where('id', '=', $request->get('pet_order_id'))
            ->first();
        if ($pet_order->type != 2 || $pet_order->cancelled != 0) {
            $this->errorResponse(Response::HTTP_OK, transtable(80006), 80006);
        }
        $this->authorize('update', $pet_order);
        try {
            DB::transaction(function () use ($user, $pet_order) {
                $pet_order = PetOrder::query()->where('id', '=', $pet_order->id)->sharedLock()->first();
                //增加用户余额
                $user->increment('balance', $pet_order->amount);
                //订单设置为取消状态
                $pet_order->cancelled = 1;
                $pet_order->save();
                //添加流水记录
                Account::create([
                    'type' => Account::CANCELLED_PRE,
                    'to' => $pet_order->user_id,
                    'amount' => $pet_order->amount,
                    'order_id' => $pet_order->id
                ]);
            }, 2);
            return $this->success();
        } catch (\Exception $exception) {
            $this->errorResponse(Response::HTTP_OK, transtable(80010), 80010);
        }
    }

}
