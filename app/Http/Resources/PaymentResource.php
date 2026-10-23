<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['upper'] = $data['upper'] / 100;
        $data['lower'] = $data['lower'] / 100;

        return $data;
    }
}
