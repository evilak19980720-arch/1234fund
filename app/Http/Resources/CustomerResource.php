<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'admin_user_id' => $this->admin_user_id,
            'contact' => $this->contact,
            'icon' => asset(Customer::ICON_MAP[$this->type]),
            'platform' => $this->type,
            'url' => $this->url ?? ''
        ];
    }
}
