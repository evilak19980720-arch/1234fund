<?php

namespace App\Http\Requests\Api\v1;


class VersionRequest extends FormRequest
{
    public function rules()
    {
        return [
            'type' => 'required|in:android,ios',
        ];
    }

    public function messages()
    {
        return [
//            'type.required' => '类型不能为空',
//            'type.in' => '类型错误',
        ];
    }
}
