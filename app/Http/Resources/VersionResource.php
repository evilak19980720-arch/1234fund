<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VersionResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);
        if (strpos($data['path'],'http') === false) {
            $data['path'] = sprintf('%s/%s', aliyun_domain(), $data['path']);
        }
        $data['force'] = $data['force'] ? true : false;

        return $data;
    }
}
