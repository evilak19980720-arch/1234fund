<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['created_at'] = $this->created_at->timestamp; //date("d-m-Y H:i",$this->created_at->timestamp); //$this->resource->created_at->toDateTimeString();
        //$data['type_str'] = trans('validation.account_type')[$this->resource->type];
        $data['type_str'] = transtable('account_type.'.$this->resource->type);
        return $data;
    }
}
