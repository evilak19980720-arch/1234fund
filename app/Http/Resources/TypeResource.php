<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Env;

class TypeResource extends JsonResource
{
    public function toArray($request)
    {
        $this->resource->makeHidden('state', 'vip_id')->toArray();

        $data = parent::toArray($request);
        $data['course'] = $data['course'] ?: '';
        if (isset($data['icon']) && $data['icon']) {
            $data['icon'] = sprintf('%s/%s', aliyun_domain(), $data['icon']);
        }

        return $data;
    }
}
