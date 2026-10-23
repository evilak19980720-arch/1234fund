<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class ActivityRequest extends FormRequest
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
                    'id' => 'required|numeric|gt:0',
                ];
                break;
        }
    }
}
