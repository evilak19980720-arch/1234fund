<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CrowdParticipateResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = date("d-m-Y H:i",$this->created_at->timestamp);


        return $data;
    }
}
