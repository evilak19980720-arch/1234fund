<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class CrowdOrderRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'GET':
                if(Str::contains(Route::currentRouteName(), 'participate.issue')){
                    return [
                        'crowd_id' => 'required|exists:crowds,id',
                        'issue' => 'required|numeric'
                    ];
                }
                if(Str::contains(Route::currentRouteName(), 'participate.user')){
                    return [
                        'page' => 'numeric|min:1',
                        'limit' => 'numeric|min:1',
                    ];
                }
                if(Str::contains(Route::currentRouteName(), 'participate.past')){
                    return [
                        'crowd_id' => 'required|exists:crowds,id',
                        'page' => 'numeric|min:1',
                        'limit' => 'numeric|min:1',
                    ];
                }
                break;
            case 'POST':
                return [
                ];
                break;
            case 'PATCH':
                return [
                ];
                break;
        }
    }
}
