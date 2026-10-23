<?php


namespace App\Http\Requests\Api\v1;

use \Illuminate\Foundation\Http\FormRequest;

class PetOrderSellRequest extends FormRequest
{
    public function rules()
    {
        return [
            'pet_order_id' => 'required|numeric'
        ];
    }
}
