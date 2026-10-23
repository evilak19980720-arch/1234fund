<?php

namespace App\Http\Requests\Api\v1;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FundOrderRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
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
            case 'POST':
                if ($this->request->get('number') < 0 && $this->request->get('size') == 'no') {
                    throw new BadRequestHttpException('You choose at least one.');
                }
                $number_quantity = $this->request->get('number') == -1 ? 0 : $this->request->get('number_quantity');
                $size_quantity = $this->request->get('size') == 'no' ? 0 : $this->request->get('size_quantity');
                if ($number_quantity <= 0 && $size_quantity <= 0) {
                    throw new BadRequestHttpException('The quantity must be at least 1.');
                }
                return [
                    'fund_id' => 'required|numeric|min:1',
                    'issue' => 'required|numeric|min:0',
                    'number' =>'required|numeric|between:-1,9',
                    'number_quantity' => 'required|numeric',
                    'size' => 'required|string|in:no,big,small',
                    'size_quantity' => 'required|numeric',
                ];
                break;
            case 'PATCH':
                return [
                    'type' => 'required|string|in:rearing,transfer'
                ];
                break;
        }
    }
}
