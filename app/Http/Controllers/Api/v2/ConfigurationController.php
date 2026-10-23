<?php

namespace App\Http\Controllers\Api\v2;

use App\Models\Configuration;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{

    /**
     * 获取用户提现手续费百分比
     * @param Request $request
     * @return mixed
     */
    public function withdrawalFee(Request $request)
    {
        return $this->success([
            'withdrawal_fee' => $request->user()->vip->withdrawal_fee,
            'withdrawal_min' => Configuration::getItem('withdrawal_min'),
            'withdrawal_max' => Configuration::getItem('withdrawal_max')
        ]);
    }
}
