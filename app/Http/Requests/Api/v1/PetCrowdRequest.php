<?php


namespace App\Http\Requests\Api\v1;

use \Illuminate\Foundation\Http\FormRequest;

class PetCrowdRequest extends FormRequest
{
    public function rules()
    {
        return [
            'crowd_id' => 'required|exists:crowds,id',
            'issue' => 'required|numeric'
        ];
    }
}
