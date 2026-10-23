<?php

namespace App\Http\Requests\Api\admin;

class AuthorizationRequest extends FormRequest
{
    public function rules()
    {
        return [
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
