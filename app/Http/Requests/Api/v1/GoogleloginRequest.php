<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class GoogleloginRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()){
            case 'POST':
                return [
                    //'phone' => 'required|regex:/^[0123456789][0-9]{9,10,11}$/',  // 手机号
                    'access_token' => 'required',  // 手机号
                    //'name' => 'required|string|unique:users,name|min:4',
                    //'password' => 'required|min:6',
                    //'verification_key' => 'required|string',
                    //'verification_code' => 'required|string',
                    //'captcha_key' => 'string',
                    //'captcha_code' => 'string',
                ];
                break;
            case 'PATCH':
                return [
                    'avatar' => 'required|numeric|between:1,16',
                ];
                break;
        }

    }
}
