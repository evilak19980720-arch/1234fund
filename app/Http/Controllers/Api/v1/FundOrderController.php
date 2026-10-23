<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\FundOrderRequest;
use App\Http\Resources\FundOrderResource;
use App\Models\FundOrder;
use App\Models\FundPeriod;
use App\Models\Pet;
use App\Models\PetOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class FundOrderController extends Controller
{
    //参与记录
    public function index(FundOrderRequest $request)
    {
        $user = $request->user();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 100);
        $page = $this->paging($page, $limit);
        $fund_order = FundOrder::query()->with(['fundPeriod.pet','fundPeriod.fund'])
            ->whereHas('user',function( Builder $query) use ($user){
                $query->where('id','=',$user->id);
            })
            ->offset($page)->limit($limit)
            ->select('id','fund_period_id','user_id','quantity','amount','win','buy','status','created_at')
            ->latest()
            ->get();
        return $this->success(FundOrderResource::collection($fund_order));
    }

    //购买众筹
    public function store(FundOrderRequest $request,FundOrder $fundOrder)
    {
        $user = $request->user();
        $now = Carbon::now();
        $fund_period = FundPeriod::query()->where('issue', $request->get('issue'))->where('fund_id',$request->get('fund_id'))->first();
        if (!$fund_period) {
            return $this->fail(404,transtable('404'));
        }
        if ($now->lt($fund_period->start_time)) {
            return $this->fail(90003,transtable('90003'));
        }
        //距离结束时间不足30秒不允许购买了
        if ($now->gt($fund_period->end_time) || (Carbon::parse($fund_period->end_time)->timestamp - time()) <= 30 ) {
            return $this->fail(90004,transtable('90004'));
        }
        //余额判断
        $buy_number_price = FundPeriod::getBuyNumberPrice($fund_period->total_price);
        $buy_size_prize = FundPeriod::getBuyBigOrSmallPrice($fund_period->total_price);
        $number_quantity = $request->get('number') == -1 ? 0 : $request->get('number_quantity');
        $size_quantity = $request->get('size') == 'no' ? 0 : $request->get('size_quantity');
        $total = $buy_number_price * $number_quantity + $buy_size_prize * $size_quantity;
        if ($total > $user->balance) {
            return $this->fail(10009,transtable('10009'));
        }
        $fundOrder->buys($fund_period,$user,$request->get('number'),$request->get('number_quantity'),$buy_number_price,$request->get('size'),$request->get('size_quantity'),$buy_size_prize);
        return $this->success();
    }


    /**
     * 转让或者饲养
     * @param FundOrder $fundOrder
     * @param FundOrderRequest $request
     * @return \Illuminate\Http\JsonResponse|object
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(FundOrder $fundOrder,FundOrderRequest $request)
    {
        //验证权限
        $this->authorize('update',$fundOrder);
        if ($request->get('type') == 'transfer') {
            //转让
            (new FundOrder())->transfer($fundOrder->id);
        } else {
            //判断饲养鱼是否超出配置限制
            $pet_holding_quantity = Pet::query()->where('id','=',$fundOrder->fundPeriod->pet->id)->value('holding_quantity');
            $user_holding = PetOrder::query()->where('user_id','=',$request->user()->id)
                ->whereIn('type',[1,2])
                ->where('state','=',0)
                ->where('pet_id','=',$fundOrder->fundPeriod->pet->id)
                ->sum('number');
            if ($user_holding > $pet_holding_quantity) {
                return $this->fail(80009,sprintf(transtable('80009'), $pet_holding_quantity));
            }
            //饲养流程
            (new FundOrder())->rearing($fundOrder->id);
        }
        return $this->success();
    }
}
