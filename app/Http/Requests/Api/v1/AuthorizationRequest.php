<?php

namespace App\Http\Requests\Api\v1;


class AuthorizationRequest extends FormRequest
{
    public function rules()
    {
        return [
            'area' => '',
            'name' => 'required',
            'password' => 'required|min:6',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Username is required',
            'password.required' => 'Password is required',
            'password.min' => 'Password at least 6 digits'
        ];
    }
}
