<?php


namespace App\Http\Requests\Api\v1;

use \Illuminate\Foundation\Http\FormRequest;

class PetTransferRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'POST':
                return [
                    'pet_order_id' => 'required|integer',
                    'phone' => 'required|numeric|digits_between:8,15'
                ];
                break;
        }
    }
}
