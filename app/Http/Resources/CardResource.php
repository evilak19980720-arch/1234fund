<?php

namespace App\Http\Resources;

use App\Models\Bank;
use App\Models\State;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class CardResource extends JsonResource
{
    public function toArray($request)
    {
        $this->resource->makeHidden('user_id','updated_at')->toArray();

        $data = parent::toArray($request);
        //$data['bank'] = Bank::getBank($data['bank_id']);
        unset($data['bank_id']);
        unset($data['province']);
        unset($data['city']);
        $data['created_at'] = $this->resource->created_at->timestamp;
        return $data;
    }
}
