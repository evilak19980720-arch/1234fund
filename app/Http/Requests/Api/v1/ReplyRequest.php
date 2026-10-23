<?php

namespace App\Http\Requests\Api\v1;

class ReplyRequest extends FormRequest
{
    public function rules()
    {
        switch ($this->method()) {
            case 'GET':
                $data = [
                    'complaint_id' => 'required|numeric|min:1',
                    'page' => 'required|numeric|min:1',
                    'limit' => 'required|numeric|min:1',
                ];
                break;
            case 'POST':
                $data = [
                    'complaint_id' => 'required|numeric|min:1',
                    'content' => 'required',
                ];
                break;
        }

        return $data;
    }
}
