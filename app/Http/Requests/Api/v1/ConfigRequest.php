<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class ConfigRequest extends FormRequest
{

    public function rules()
    {
        return [
            'name' => "required|in:graphic"
        ];
    }

    public function attributes()
    {
        return [
            'name' => '名称'
        ];
    }
}
