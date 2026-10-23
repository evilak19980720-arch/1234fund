<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'GET':
                return [
                    'type' => 'required|in:withdrawal,recharge',
                    'page' => 'required|numeric|min:1',
                    'limit' => 'required|numeric|min:1',
                    'state' => 'required|numeric',

                ];
                break;
            case 'POST':
                return [
                    'order_id' => 'required|numeric|exists:orders,id'
                ];
                break;
            case 'PATCH':
                return [
                ];
                break;
        }

    }
}
