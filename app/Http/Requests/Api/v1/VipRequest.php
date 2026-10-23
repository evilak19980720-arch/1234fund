<?php

namespace App\Http\Requests\Api\v1;


use App\Models\Vip;

class VipRequest extends FormRequest
{
    public function rules()
    {
        $vips = Vip::getVip();
        $vipStr = '';
        foreach ($vips as $vip) {
            $vipStr .= $vip->id . ',';
        }
        $vipStr = trim($vipStr,',');

        return [
            'vip' => 'required|in:'.$vipStr,
//            'password' => 'required|digits:6',
        ];
    }
}
