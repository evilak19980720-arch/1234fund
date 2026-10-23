<?php

namespace App\Http\Requests\Api\v1;


class SocialAuthorizationRequest extends FormRequest
{
    public function rules()
    {
        $rules = [];

        switch ($this->social_type) {
            case 'sms':
                $rules['verification_key'] = 'required|string';
                $rules['verification_code'] = 'required|string';
                break;
            case 'weixin':
                $rules['weixin_unionid'] = 'required|string';
                $rules['weixin'] = 'required|string';
                $rules['avatar'] = 'required|string';
                break;
        }

        return $rules;
    }

    public function attributes()
    {
        return [
            'verification_key' => '短信验证码 key',
            'verification_code' => '短信验证码',
            'weixin_unionid' => '微信unionid'
        ];
    }

    public function messages()
    {
        return [
            'verification_key.required' => '验证码key不能为空',
            'verification_code.required' => '短信码不能为空',
            'weixin_unionid.required' => '微信unionid不能为空',
            'weixin.required' => '微信号不能为空',
            'avatar' => '微信头像不能为空'
        ];
    }
}
