<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class PayRequest extends FormRequest
{
    public function rules()
    {
        return [
            'amount' => 'required|numeric|gt:0',
            //'payment_id' => 'required|numeric|gt:0',
            'currery' => 'required'
        ];
    }
}
