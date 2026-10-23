<?php

namespace App\Http\Resources;

use App\Models\Vip;
use Illuminate\Http\Resources\Json\JsonResource;

class RankingResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $vips = Vip::getVip();
        foreach ($vips as $vip) {
            if ($data['vip_id'] == $vip->id) {
                $data['vip']['id'] = $vip->id;
                $data['vip']['name'] = $vip->name;
                $data['vip']['level'] = $vip->level;
            }
        }
        unset($data['vip_id']);

        return $data;
    }
}
