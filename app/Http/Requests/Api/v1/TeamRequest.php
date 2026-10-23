<?php

namespace App\Http\Requests\Api\v1;


class TeamRequest extends FormRequest
{
    public function rules()
    {
        return [
            'level' => 'in:0,1,2,3',
            'page' => 'numeric|min:1',
            'limit' => 'numeric|min:1',
            'date' => 'date|after:2019-12|before:tomorrow',
            'showzero' => 'in:0,1',
            'phone' => 'string|nullable',
            'sort' => 'string|nullable|in:desc,asc',
            'column' => 'string|nullable|in:contribution,time'
        ];
    }
}
