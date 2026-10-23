<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TaskLogResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = $this->resource->created_at->timestamp;
        unset($data['task_id']);

        return $data;
    }
}
