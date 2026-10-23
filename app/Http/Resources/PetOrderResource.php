<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class PetOrderResource extends JsonResource
{
    private $type_str = [1 => 'Purchased', 2 => 'Reserved', 3 => 'Sold'];

    public function toArray($request)
    {
        $data = parent::toArray($request);


        $data['can_sell'] = 0;
        $data['var_daily'] = 0;
        if ($data['type'] == 1) {
            $data['var_daily'] = intval((strtotime(date('Y-m-d')) - strtotime($data['start_at'])) / 3600 / 24);

            //if($data['var_daily'] > 0 && time() < strtotime(date('Y-m-d 12:01:00'))){
            //    $data['var_daily'] = $data['var_daily']-1;
            //}

            //是否可以卖出
            //$data['can_sell'] = 0;
            if ($data['var_daily'] >= $data['pet']['day']) {
                $data['var_daily'] = $data['pet']['day'];
                //$data['can_sell'] = 1;
            }
            //if ($data['state'] == 1) {
            //    $data['can_sell'] = 0;
            //}
        }


        $data['created_at'] = date('d/m/Y', strtotime($this->resource->created_at));
        if (!empty($data['start_at'])) {
            $data['end'] = strtotime($data['start_at']) + ($data['pet']['day'] + 1) * 24 * 60 *60;

            if($data['end'] <  time() && $data['state'] == 0){
                $data['can_sell'] = 0;
            }

            $data['start_at'] = date('d/m/Y', strtotime($data['start_at']));
        }

        if($data['state'] == 1){
            $data['var_daily'] = $data['pet']['day'];
        }

        return $data;
    }
}
