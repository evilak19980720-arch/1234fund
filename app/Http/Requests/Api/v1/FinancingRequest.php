<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class FinancingRequest extends FormRequest
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
                    'amount' => 'required|numeric',
                    'whatsapp' => 'required|max:250',
                    'project_name' => 'required|max:250',
                    'project_plan' => 'required|max:250',
                ];
                break;
            case 'PATCH':
                return [
                ];
                break;
            case 'DELETE':
                return [
                ];
                break;
        }
    }
}
