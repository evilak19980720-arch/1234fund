<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{

    public function toArray($request)
    {
        $this->resource->makeHidden('type','user_id','created_at','updated_at')->toArray();

        $data = parent::toArray($request);

        return $data;
    }
}
