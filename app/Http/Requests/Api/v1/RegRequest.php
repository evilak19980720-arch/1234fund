<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class RegRequest extends FormRequest
{
    public function rules()
    {
        return [
            'area' => 'required|numeric',   // 区号
            'phone' => 'required|numeric|digits_between:8,15',  // 手机号
            'password' => 'required|min:6',
            'captcha_key' => 'required',
            'captcha_code' => 'required',
            'device_number'=> 'required|string|min:10'
        ];
    }
}
