<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class FriendRequest extends FormRequest
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
                    //'content' => 'required|max:250',
                    'imgs' => 'required',
                ];
                break;
            case 'PATCH':
                return [
                ];
                break;
            case 'DELETE':
                return [
                ];
                break;
        }
    }
}
