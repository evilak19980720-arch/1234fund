<?php

namespace App\Http\Requests\Api\v1;

class RechargeFindRequest extends FormRequest
{
    public function rules()
    {
        return [
            'trade_no' => 'required|exists:recharge,trade_no',
            'pay_way'  => 'required|in:1,2',
        ];
    }

    public function messages()
    {
        return [
            'trade_no.required' => '订单号不能为空',
            'trade_no.exists'   => '订单号不存在',
            'pay_way.required'  => '充值方式不能为空',
            'pay_way.in'        => '充值方式错误',
        ];
    }
}
