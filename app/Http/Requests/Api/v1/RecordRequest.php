<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class RecordRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'POST':
                return [
                    'type' => 'required|in:ios,android,h5',
                    'device' => 'required'
                ];
                break;
        }
    }
}
