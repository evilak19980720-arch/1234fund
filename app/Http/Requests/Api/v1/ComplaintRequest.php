<?php

namespace App\Http\Requests\Api\v1;


class ComplaintRequest extends FormRequest
{
    public function rules()
    {
        return [
            'type' => 'required|in:complaint,tip-off',
            'title' => 'required|alpha_dash|min:3',
            'content' => 'required|string|min:10',
            'contact' => 'required',
        ];
    }
}
