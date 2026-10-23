<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray($request)
    {
        $this->resource->makeHidden('created_at','updated_at')->toArray();

        $data = parent::toArray($request);
        $data['path'] = sprintf('%s/%s', aliyun_domain(), $data['path']);
        $data['image'] = $data['path'];
        $data['url'] = $data['url'] ?: '';
        return $data;
    }
}
