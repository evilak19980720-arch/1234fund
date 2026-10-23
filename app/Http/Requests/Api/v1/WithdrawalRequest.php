<?php

namespace App\Http\Requests\Api\v1;


class WithdrawalRequest extends FormRequest
{
    public function rules()
    {
        return [
            'amount' => 'required|numeric|gt:0',
//            'password' => 'required',
        ];
    }
}
