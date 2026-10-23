<?php

namespace App\Http\Requests\Api\v1;


class CrowdShowRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'GET':
                return [
                    'id' => 'required|numeric|exists:crowds',
                    'issue' => 'numeric|min:1'
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
