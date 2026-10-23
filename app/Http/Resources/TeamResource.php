<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['created_at'] = $this->resource->created_at->timestamp;
        return $data;
    }
}
