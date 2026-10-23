<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\FundPeriodRequest;
use App\Http\Resources\FundOrderResource;
use App\Http\Resources\FundPeriodResource;
use App\Models\Fund;
use App\Models\FundOrder;
use App\Models\FundPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class FundPeriodController extends Controller
{

    /**
     * 页码
     * @var int
     */
    public int $page;

    /**
     * 每页显示数量
     * @var int
     */
    public int $limit;

    public function __construct(Request $request)
    {
        $page = $request->get('page', 1);
        $this->limit = $request->get('limit', 10);
        $this->page = $this->paging($page, $this->limit);
    }

    public function index(FundPeriodRequest $request)
    {
        $user = $request->user();
        $now = Carbon::now();
        $period = Fund::query()->with(['fundPeriod' => function($query) use ($now){
            $query->where('start_time', '<=', $now)
                ->where('end_time', '>', $now)
                ->select('id','fund_id','pet_id','issue','total_price','start_time','end_time')->orderBy('id','asc');
        },'fundPeriod.pet'])
            ->where('admin_user_id','=',$user->admin_user_id)
            ->where('state','=',Fund::ON)
            ->select('id','desc','sort')
            ->offset($this->page)->limit($this->limit)
            ->orderBy('sort', 'desc')
            ->get()->toArray();
        foreach ($period as $key => $item) {
            if (count($item['fund_period']) == 0) {
                $period[$key]['fund_period'] = FundPeriod::query()->with('pet')
                    ->where('fund_id','=',$item['id'])
                    ->where('start_time', '>=', $now)
                    ->select('id','fund_id','pet_id','issue','total_price','start_time','end_time')
                    ->orderBy('sort', 'desc')->first();
            } else {
                $period[$key]['fund_period'] = Arr::first($item['fund_period']);
            }
            //防止排期未生成
            if (!$period[$key]['fund_period']) {
                unset($period[$key]);
            }
        }
        return $this->success(FundPeriodResource::collection($period));
    }


    //详情
    public function show(Request $request,int $fund_id,FundPeriod $fundPeriod,FundOrder $fundOrder)
    {
        $user = $request->user();
        $now = Carbon::now();
        $period = FundPeriod::query()
            ->with(['pet','fund:id,desc'])
            ->where('fund_id','=',$fund_id)
            ->where('admin_user_id','=',$user->admin_user_id)
            ->where('start_time', '<=',  $now)
            ->where('end_time', '>',$now)
            ->select('id','fund_id','pet_id','issue','total_price','start_time','end_time')
            ->first();
        if (!$period) {
            $period = FundPeriod::query()
                ->with(['pet','fund:id,desc'])
                ->where('fund_id','=',$fund_id)
                ->where('admin_user_id','=',$user->admin_user_id)
                ->where('end_time', '>',$now)
                ->select('id','fund_id','pet_id','issue','total_price','start_time','end_time')
                ->first();
        }
        if (!$period) {
            return $this->fail(404,trans('error.404'));
        }
        $data['period'] = $period;
        //前10期开奖结果
        $data['winning_record'] = $fundPeriod->winningRecord($fund_id,10);
        //购买记录
        $data['buy_record'] = $period ? FundOrderResource::collection($fundOrder->record($period->id)) : [];
        return $this->success(new FundPeriodResource($data));
    }


    //获取彩种历史开奖记录
    public function record(FundPeriodRequest $request,int $fund_id)
    {
        $record = FundPeriod::query()
            ->where('fund_id','=',$fund_id)
            ->where('admin_user_id','=',$request->user()->admin_user_id)
            ->where('end_time', '<', Carbon::now())
            ->select('id','issue','draw_number','draw_size','end_time','win_number as number_of_winners')
            //不展示真实中奖人数
//            ->withCount(['fundOrder as number_of_winners' => function($query){
//                $query->where('win','=',FundOrder::WIN_YES);
//            }])
            ->offset($this->page)->limit($this->limit)
            ->orderBy('id','desc')
            ->get();
        $record->map(function($value,$key){
            $value['end_time'] = date('Y-m-d H:i',Carbon::parse($value['start_time'])->timestamp);
            return $value;
        });
        return $this->success($record);
    }

}
