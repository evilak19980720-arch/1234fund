<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class OtherLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'data' => 'required|string',  // data
        ];
    }
}
