<?php


namespace App\Http\Requests\Api\v1;

use \Illuminate\Foundation\Http\FormRequest;

class PetOrderRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'POST':
                return [
                    'pet_id' => 'required',
                    'type' => 'required|in:1,2',
                    'number' => 'integer|between:1,99999',
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
