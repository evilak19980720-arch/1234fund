<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CrowdOrderResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['crowd']['pet']['image'] = sprintf('%s/%s', aliyun_domain(), $data['crowd']['pet']['image']);
        if($data['issues']){
            $data['issues']['end_time'] = Carbon::parse($data['issues']['end_time'])->timestamp;
            $data['issues']['now_time'] =  Carbon::now()->timestamp;

            // 时间未到 未开奖返回0
            if($data['issues']['end_time'] > $data['issues']['now_time']){
                $data['issues']['draw'] = 0;
            }
        }
        return $data;
    }
}
