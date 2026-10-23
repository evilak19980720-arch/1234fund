<?php

namespace App\Http\Resources;

use App\Models\FundOrder;
use App\Models\FundPeriod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FundOrderResource extends JsonResource
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
        if (isset($data['user'])) {
            //排期详情购买记录
            $data['link'] = '';
            $data['phone'] = $data['user']['phone'];
            $data['avatar'] = $data['user']['avatar'];
            if($data['user']['hidephone'] == 0 ){
                $data['link'] = sprintf('%s=+91%d',User::WHATAPP_URL,$data['user']['phone']);
            }
            if($data['user']['hidephone'] == 1){
                $data['phone'] = sprintf('%s****%s', substr($data['user']['phone'], 0, 3), substr($data['user']['phone'], -3));
            }
            unset($data['user']);
        } else {
            //用户购买记录
            $data['issue'] = $data['fund_period']['issue'];
            $data['pet_name'] = $data['fund_period']['pet']['name'];
            $data['pet_image'] = sprintf('%s/%s', aliyun_domain(), $data['fund_period']['pet']['image']);
            $data['start_time'] = Carbon::parse($data['fund_period']['start_time'])->timestamp;
            $data['end_time'] = Carbon::parse($data['fund_period']['end_time'])->timestamp;
            if (time() < $data['end_time']) {
                //倒计时未结束 不可以返回开奖状态,开奖结果等信息
                $data['win'] = -1;//未开奖
                $data['draw'] = FundPeriod::DRAW_NO;
                $data['draw_number'] = -1;
                $data['draw_size'] = 'no';
            } else {
                $data['draw'] = $data['fund_period']['draw'];
                $data['draw_number'] = $data['fund_period']['draw_number'];
                $data['draw_size'] = $data['fund_period']['draw_size'];
            }
            $total_price = $data['fund_period']['total_price'] * $data['quantity'];
            $rate = $data['fund_period']['fund']['rate'];
            $data['rate'] = $rate;
            $data['transfer_amount'] = $total_price - intval($total_price * ($rate / 100));  //手续费
            $data['now'] = time();
            unset($data['fund_period']);
        }
        $data['created_at'] = date('Y-m-d H:i',Carbon::parse($data['created_at'])->timestamp);
        return $data;
    }
}
