<?php

namespace App\Http\Requests\Api\v1;


class TaskRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'GET':
                return [
                    'type_id' => 'required|numeric|min:1',
                    'page' => 'required|numeric|min:1',
                    'limit' => 'required|numeric|min:1',
                ];
                break;
            case 'POST':
                return [
                    'id' => 'required|numeric|min:1',
                ];
                break;
            case 'PATCH':
                return [
                    'id' => 'required|numeric|min:1',
//                    'img' => 'required',
                ];
                break;
        }
    }
}
