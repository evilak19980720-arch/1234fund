<?php


namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;


class PaymentDocRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'GET':
                return [
                    'order_id' => 'required',
                ];
                break;
            case 'POST':
                return [
                    'order_id' => 'required',
                    'image_url' => 'required',
                ];
                break;
        }
    }
}
