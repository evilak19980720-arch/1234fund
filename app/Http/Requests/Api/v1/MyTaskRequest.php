<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class MyTaskRequest extends FormRequest
{
    public function rules()
    {
        return [
            'type' => 'required|in:conduct,audit,complete,fail',
            'page' => 'required|numeric|min:1',
            'limit' => 'required|numeric|min:1',
        ];
    }
}
