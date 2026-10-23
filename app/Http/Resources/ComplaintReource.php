<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ComplaintReource extends JsonResource
{
    public function toArray($request)
    {
        $this->resource->makeHidden('user_id','updated_at')->toArray();

        $data = parent::toArray($request);
        $data['created_at'] = Carbon::parse($this->resource->created_at)->format('Y-m-d H:i');
        return $data;
    }
}
