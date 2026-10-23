<?php

namespace App\Http\Requests\Api\v1;


class AddressbookRequest extends FormRequest
{
    public function rules()
    {
        return [
            'books' => 'required'
        ];
    }
}
