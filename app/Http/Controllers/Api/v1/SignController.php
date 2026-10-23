<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\AdminUser;
use App\Models\Configuration;
use App\Models\SignDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SignController extends Controller
{


    public function store(Request $request, SignDetail $sign_detail)
    {
        try {
            //必须有今日的抢购记录才允许签到
            $user = $request->user();
            if (!Configuration::getItem('sign_in') || !Configuration::getItem('sign_in_reward') || !Configuration::getItem('sign_in_subordinate')) {
                return $this->fail(10034, transtable('10034'));
            }
            if ($user->countSubordinate(1) < Configuration::getItem('sign_in_subordinate')) {
                return $this->fail(10033, sprintf(transtable('10033'), Configuration::getItem('sign_in_subordinate')));
            }
            $sign_detail->sign($user);
            return $this->success();
        } catch (\Exception $exception) {
            return $this->fail(10035, transtable('10035'));
        }
    }
}
