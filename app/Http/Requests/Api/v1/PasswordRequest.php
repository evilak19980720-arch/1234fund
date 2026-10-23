<?php

namespace App\Http\Requests\Api\v1;


class PasswordRequest extends FormRequest
{
    public function rules()
    {
        return [
            'current_password' => 'required',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required|same:password'
        ];
    }
}
