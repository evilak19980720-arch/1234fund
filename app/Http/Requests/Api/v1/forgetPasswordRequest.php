<?php

namespace App\Http\Requests\Api\v1;


class forgetPasswordRequest extends FormRequest
{

    public function rules()
    {
        return [
            'password' => 'required|min:6',
            'verification_key' => 'required|string',
            'verification_code' => 'required|digits:6'
        ];
    }
}
