<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{

    //Succeed   Faild  Pending
    private $status;

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $this->status = [
            -1 => transtable('order.Pending'),
            0 => transtable('order.Pending'),
            1 => transtable('order.Success'),
            2 => transtable('order.Faild'),
            3 => transtable('order.Faild'),
            4 => transtable('order.Faild'),
        ];

        $data['status'] = $this->status[$this->state];
        $data['remark'] = $this->remark ?: '';
        $data['created_at'] = date("d-m-Y H:i",$this->created_at->timestamp);

        return $data;
    }
}
