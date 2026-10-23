<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\VerificationCodeRequest;
use App\Models\Configuration;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;


use Overtrue\EasySms\EasySms;

//use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\PhoneNumber;

class VerificationCodesController extends Controller
{
    private int $limit = 10;
    private int $minutes = 60;

    public function store(VerificationCodeRequest $request)
    {
        if(Str::limit($request->phone,1,'') == '0'){
            $request->phone = preg_replace('/^0+/','',$request->phone);
            if(empty($request->phone)){
                $this->errorResponse(Response::HTTP_OK, transtable('10017'), 10017);
            }
        }

        if (Cache::get(sprintf('phone:%s', $request->phone))) {
            $this->errorResponse(Response::HTTP_OK, transtable('10017'), 10017);
        }
        $this->frequency($request->phone);
        // 图形码验证
        /*
        if (Configuration::getItem('captcha') && ($request->get('type') === 'register' || $request->get('type') === 'forget')) {
            $captchaData = Cache::get($request->get('captcha_key'));
            if (!$captchaData) {
                $this->errorResponse(Response::HTTP_OK, trans('error.30002'), 30002);
            }

            if (!hash_equals($captchaData['code'], strtolower($request->get('captcha_code')))) {
                // 验证错误就清除缓存
                Cache::forget($request->get('captcha_key'));
                $this->errorResponse(Response::HTTP_OK, trans('error.30003'), 30003);
            }
        }
        */

        $phone = $request->phone;
        // 通用验证码
        if (!app()->environment('production')) {
            $code = '123456';
        } else {
            $code = str_pad(random_int(100000, 999999), 6, 0, STR_PAD_LEFT);
            $this->sendSms($phone, '91', $code);
            //$this->sendSms($phone, $request->get('area'), $code);
        }

        $key = 'verificationCode_' . Str::random(15);
        $expiredAt = now()->addMinutes(5);
        // 缓存验证码 5 分钟过期。
        Cache::put($key, ['phone' => $phone, 'code' => $code], $expiredAt);
        //30秒钟 只能发一次
        Cache::put(sprintf('phone:%s', $phone), 'true', now()->addSeconds(30));
        return $this->success([
            'key' => $key,
            'expired_at' => $expiredAt->timestamp
        ]);
    }


    /**
     * @param $phone
     * @param $area
     * @param $code
     */
    private function sendSms($phone, $area, $code)
    {
        //根据配置使用不用的短信
        switch (config('smsServiceProvider.sms_service_provider')) {
            case 'easySms':
                $this->easySms($phone, $area, $code);
                break;
            case 'kmiCloud':
                $this->kmiCloud($phone, $area, $code);
                break;
            case 'nodeSms':
                $this->nodeSms($phone, $area, $code);
                break;
            case 'xqtxhn':
                $this->xqtxhn($phone, $area, $code);
                break;
        }
    }

    private function frequency($phone)
    {
        if (Cache::has($phone)) {
            $this->errorResponse(Response::HTTP_OK, transtable('10017'), 10017);
        }

        $key = sprintf('sms:%s', $phone);
        if (Cache::increment($key) >= $this->limit) {
            Cache::add($phone, 1, Carbon::now()->addMinutes($this->minutes));
            Cache::forget($key);
        }
    }


    private function easySms($phone, $area, $code)
    {
        try {
            $easySms = app('easySms');
            $number = new PhoneNumber($phone, $area);
            $easySms->send($number, sprintf('%d is your verification code, please verify the identity. please do not share this OTP. [TIG]', $code));
        } catch (NoGatewayAvailableException $exception) {
            $this->errorResponse(Response::HTTP_OK, transtable('10000'), 10000);
        }
    }

    private function kmiCloud($phone, $area, $code)
    {
        try {
            $sms = app('kmiCloud');
            $sms->send($area . $phone, $code);
        } catch (\Exception $exception) {
            $this->errorResponse(Response::HTTP_OK, transtable('10000'), 10000);
        }
    }

    private function nodeSms($phone, $area, $code)
    {
        try {
            $sms = app('nodeSms');
            $sms->send($area . $phone, $code);
        } catch (\Exception $exception) {
            $this->errorResponse(Response::HTTP_OK, transtable('10000'), 10000);
        }
    }

    private function xqtxhn($phone, $area, $code)
    {
        try {
            $sms = app('xqtxhn');
            $sms->send($area . $phone, $code);
        } catch (\Exception $exception) {
            $this->errorResponse(Response::HTTP_OK, transtable('10000'), 10000);
        }
    }


}
