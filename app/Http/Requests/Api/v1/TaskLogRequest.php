<?php

namespace App\Http\Requests\Api\v1;


class TaskLogRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'PATCH':
                return [
                    'id' => 'required|numeric|min:1',
                ];
                break;
        }
    }
}
