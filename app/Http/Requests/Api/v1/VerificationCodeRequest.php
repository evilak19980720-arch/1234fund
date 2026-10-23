<?php

namespace App\Http\Requests\Api\v1;

class VerificationCodeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'area' => 'required|numeric',   // 区号
            'phone' => 'required|numeric|digits_between:8,15',  // 手机号
            'type' => 'required|in:register,other,forget',
            //'captcha_key' => 'required_if:type,register',
            //'captcha_code' => 'required_if:type,register',
        ];
    }

    public function messages()
    {
        return [
            'area.required' => 'area code is required',
            'area.numeric' => 'incorrect area code',
            'phone.required' => 'Phone number cannot be empty',
            'phone.regex' => 'Please enter a valid phone number',
        ];
    }
}
