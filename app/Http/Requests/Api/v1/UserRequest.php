<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()){
            case 'POST':
                return [
                    'phone' => 'required|numeric|digits_between:8,15',  // 手机号
                    'password' => 'required|min:6',
                ];
                break;
            case 'PATCH':
                return [
                    'avatar' => 'string',
                    'hidephone' => 'numeric|between:0,1',
                    'whatsapp'  => 'numeric'
                ];
                break;
        }

    }
}
