<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PetActivityResource extends JsonResource
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

        unset($data['max_stock_quantity']);
        //unset($data['act_rate_of_return']);
        //unset($data['act_profit']);
        unset($data['name']);
        unset($data['stock_quantity']);

        /*
        if (strpos($data['image'], 'http') === false) {
            $data['image'] = sprintf('%s/%s', aliyun_domain(), $data['image']);
        }
*/
        $data['end_at'] = $endAtTimeStamp = is_string($data['end_at']) ? strtotime($data['end_at']) : $data['end_at'];
        $data['start_at'] = $startAtTimeStamp = is_string($data['start_at']) ? strtotime($data['start_at']) : $data['start_at'];
        /*

        $leftSeconds = $endAtTimeStamp - Carbon::now()->timestamp;
        $data['left_seconds'] = $leftSeconds > 0 ? $leftSeconds : 0;

        $totalSeconds = $endAtTimeStamp - $startAtTimeStamp;
        $totalSeconds = $totalSeconds > 0 ? $totalSeconds : 1;
//        var_dump($totalSeconds);exit;
        $numPerSec = $data['max_stock_quantity'] / $totalSeconds;

        //每秒数量小于1和大于1的处理
        $data['num_per_sec'] = $numPerSec > 1 ? floor($numPerSec) : 0;
        $data['surplus'] = $data['num_per_sec'] > 0 ? $data['num_per_sec'] * $data['left_seconds'] : ceil($numPerSec * $data['left_seconds']);
        $data['surplus'] = $data['surplus'] < 0 ? 0 : $data['surplus'];

        if ($data['num_per_sec'] > 1)
        {
            $data['surplus'] += $data['num_per_sec'] % 20;
        }

        //$data['rental_type'] = trans('validation.rental_type')[$data['rental_type']];

        $data['max_stock_quantity'] = $data['stock_quantity'];

        */
        return $data;
    }
}
