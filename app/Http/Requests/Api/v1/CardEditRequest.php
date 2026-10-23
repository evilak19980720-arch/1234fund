<?php

namespace App\Http\Requests\Api\v1;

use App\Models\Configuration;

class CardEditRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'card_id' => 'required',
            'number' => 'required|string|min:8|max:50',
            //'number_confirmation' => 'required|same:number',
            //'username' => 'required|regex:/(^[A-Za-z ]+$)+/',
            //'phone' => 'required',
            //'email' => 'required',
            //'ifsc' => 'regex:/^[A-Za-z0-9]{4}0[A-Za-z0-9]{6}$/',
            //'ifsc' => 'required',
            //'verification_key' => 'required',
            //'verification_code' => 'numeric',
        ];
    }

    public function messages(): array
    {
        return [
            //'ifsc.size' => 'IFSC SHOULD BE OF 11 CHARACTERS AND 5TH CHARACTER SHOULD BE 0',
            //'ifsc.regex' => 'IFSC SHOULD BE OF 11 CHARACTERS AND 5TH CHARACTER SHOULD BE 0',
        ];
    }

}
