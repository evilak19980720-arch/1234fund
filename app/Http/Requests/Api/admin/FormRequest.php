<?php

namespace App\Http\Requests\Api\admin;

use Illuminate\Foundation\Http\FormRequest as BaseRequest;

class FormRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }


}
