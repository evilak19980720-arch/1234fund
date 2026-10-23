<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VipResource extends JsonResource
{
    public function toArray($request)
    {
        $this->resource->makeHidden('id')->toArray();

        $data = parent::toArray($request);

        return $data;
    }
}
