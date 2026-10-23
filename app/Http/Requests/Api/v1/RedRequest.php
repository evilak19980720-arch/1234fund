<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class RedRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'POST':
                return [
                    //'red_ladder_id' => 'required',
                ];
                break;
            case 'PATCH':
                return [
                    'id' => 'required|numeric|gt:0',
                    'client' => 'required|in:ios,android,h5',
                    'device' => 'required'
                ];
                break;
        }
    }
}
