<?php

namespace App\Http\Requests\Api\v1;

class RechargeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'vip'    => 'required|in:1,2,3,4,5,6',
            'pay_way' => 'required|in:1,2',
        ];
    }

    public function messages()
    {
        return [
            'vip.required'    => '充值金额不能为空',
            'vip.in'          => '充值金额错误',
            'pay_way.required' => '充值方式不能为空',
            'pay_way.in'       => '充值方式错误',
        ];
    }
}
