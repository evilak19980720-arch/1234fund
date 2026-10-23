<?php

namespace App\Http\Requests\Api\v1;


class IssueRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'GET':
                return [
                    'page' => 'required|numeric|min:1',
                    'limit' => 'required|numeric|min:1',
                ];
                break;
            case 'POST':
                return [
                ];
                break;
            case 'PATCH':
                return [
                ];
                break;
        }
    }
}
