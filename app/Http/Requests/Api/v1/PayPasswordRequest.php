<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class PayPasswordRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'POST':
                $rule = [
                    'password' => 'required|confirmed|digits:6',
                    'password_confirmation' => 'required|same:password'
                ];
                break;
            case 'PUT':
                $rule = [
                    'password' => 'required|confirmed|digits:6',
                    'password_confirmation' => 'required|same:password',
                    'verification_key' => 'required|string',
                    'verification_code' => 'required|digits:6'
                ];
                break;
        }
        return $rule;
    }
}
