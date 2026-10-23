<?php

namespace App\Http\Resources;

use App\Models\FundPeriod;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FundPeriodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        if (isset($data['period'])) {
            //详情
            $data['period']['start_time'] = Carbon::parse($data['period']['start_time'])->timestamp;
            $data['period']['end_time'] = Carbon::parse($data['period']['end_time'])->timestamp;
            $data['period']['pet_name'] = $data['period']['pet']['name'];
            $data['period']['pet_image'] = sprintf('%s/%s', aliyun_domain(), $data['period']['pet']['image']);
            $data['period']['desc'] = $data['period']['fund']['desc'];
            $data['period']['buy_number_price'] = FundPeriod::getBuyNumberPrice($data['period']['total_price']);
            $data['period']['buy_big_or_small_price'] = FundPeriod::getBuyBigOrSmallPrice($data['period']['total_price']);
            $data['period']['now'] = Carbon::now()->timestamp;
            //处理一下购买记录
            unset($data['period']['pet'],$data['period']['fund']);
        } else {
            //列表
            $data['fund_id'] = $data['fund_period']['fund_id'];
            $data['start_time_date'] =  $data['fund_period']['start_time'];
            $data['end_time_date'] =  $data['fund_period']['end_time'];
            $data['start_time'] = Carbon::parse($data['fund_period']['start_time'])->timestamp;
            $data['end_time'] = Carbon::parse($data['fund_period']['end_time'])->timestamp;
            $data['pet_name'] = $data['fund_period']['pet']['name'];
            $data['pet_image'] = sprintf('%s/%s', aliyun_domain(), $data['fund_period']['pet']['image']);
            $data['buy_number_price'] = FundPeriod::getBuyNumberPrice($data['fund_period']['total_price']);
            $data['buy_big_or_small_price'] = FundPeriod::getBuyBigOrSmallPrice($data['fund_period']['total_price']);
            $data['pet_id'] =  $data['fund_period']['pet_id'];
            $data['issue'] = $data['fund_period']['issue'];
            $data['total_price'] = $data['fund_period']['total_price'];
            $data['now'] = Carbon::now()->timestamp;
            unset($data['fund_period']);
        }
        return $data;
    }
}
