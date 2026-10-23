<?php

namespace App\Http\Requests\Api\v1;


class CardRequest extends FormRequest
{
    public function rules()
    {
        return [
            //'number' => 'required|numeric|confirmed|unique:cards,number',
            'number' => 'required|string|min:8|max:50',
            //'number_confirmation' => 'required|same:number',
            //'username' => 'required',
            //'verification_key' => 'required',
            //'verification_code' => 'required|numeric',
            //'email' => 'required|email',
//            'bank_id' => 'required|numeric|between:1,10000',
            //'phone' => 'required',
            //'province' => 'required|numeric|between:1,757',
            //'city' => 'required|numeric|between:1,757',
            //'ifsc' => 'required',
            //'address' => 'required',
            'currery' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'province.between' => 'province error',
            'city.between' => 'city error',
        ];
    }
}
