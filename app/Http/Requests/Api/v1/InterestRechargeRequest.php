<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class InterestRechargeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'amount' => 'required|integer|gt:0'
        ];
    }
}
