<?php

namespace App\Http\Requests\Api\v1;


class IncomeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'mold' => 'numeric',
            'type' => 'required|in:whole,income,expenditure',
            'date' => 'date|after:2019-12|before:tomorrow',
            'page' => 'required|numeric|min:1',
            'limit' => 'required|numeric|min:1',
        ];
    }
}
