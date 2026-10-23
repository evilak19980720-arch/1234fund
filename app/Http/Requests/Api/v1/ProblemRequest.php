<?php

namespace App\Http\Requests\Api\v1;


class ProblemRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => 'required|min:1',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Question title is required',
            'title.min' => 'Your question is too short',
        ];
    }
}
