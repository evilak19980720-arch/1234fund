<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Env;

class TaskResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);

        isset($data['start']) && $data['start'] = $this->resource->start->toDateTimeString();
        isset($data['created_at']) && $data['created_at'] = $this->resource->created_at->toDateTimeString();
        isset($data['updated_at']) && $data['updated_at'] = $this->resource->updated_at->toDateTimeString();
        if (isset($data['created_at']) && isset($data['updated_at']) && $data['created_at'] == $data['updated_at']) {
            $data['updated_at'] = 0;
        }
        if (isset($data['icon']) && $data['icon']) {
            $data['icon'] = sprintf('%s/%s', aliyun_domain(), $data['icon']);
        }
        $data['state_str'] = trans('validation.mission_type')[$this->resource->state];
        return $data;
    }
}
