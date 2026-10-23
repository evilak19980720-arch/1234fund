<?php

namespace App\Http\Requests\Api\v1;


class CrowdRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'GET':
                return [
                    'page' => 'numeric|min:1',
                    'limit' => 'numeric|min:1',
                    'issue' => 'numeric|min:1'
                ];
                break;
            case 'POST':
                return [
                    'crowd_id' => 'required|numeric|min:1',
                    'issue' => 'required|numeric|min:0',
                    'quantity' => 'required|numeric|min:1'
                ];
                break;
            case 'PATCH':
                return [
                ];
                break;
        }
    }
}
