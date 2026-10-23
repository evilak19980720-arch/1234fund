<?php

namespace App\Http\Requests\Api\v1;


class FundPeriodRequest extends FormRequest
{
    /**
     * @return array|string[]
     */
    public function rules()
    {
        switch ($this->method()) {
            case 'GET':
                return [
                    'page' => 'numeric|min:1',
                    'limit' => 'numeric|min:1',
                ];
                break;
        }
    }
}
