<?php

namespace App\Http\Requests\Api\v1;


class ManualRequest extends FormRequest
{
    public function rules()
    {
        return [
            'page' => 'required|numeric|min:1',
            'limit' => 'required|numeric|min:1',
        ];
    }
}
