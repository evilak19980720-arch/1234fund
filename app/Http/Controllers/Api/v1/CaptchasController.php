<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Configuration;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CaptchasController extends Controller
{
    public function store(Request $request,CaptchaBuilder $captchaBuilder)
    {
        $key = 'captcha-'.Str::random(15);
        $phraseBuilder = new PhraseBuilder(4,'23456789abcdefhkprstxy');
        $captchaBuilder = new CaptchaBuilder(null,$phraseBuilder);

        $captchaBuilder->setDistortion(false);
        $captchaBuilder->setIgnoreAllEffects(true);
        $captchaBuilder->setMaxOffset(0);

        //设置背景颜色
        // $captchaBuilder->setBackgroundColor(34, 0, 45);
        //设置倾斜角度
        $captchaBuilder->setMaxAngle(0);
        //设置验证码后面最大行数
        $captchaBuilder->setMaxBehindLines(10);


        $captcha = $captchaBuilder->build();
        $expiredAt = now()->addMinutes(2);
        Cache::put($key, ['code' => strtolower($captcha->getPhrase())], $expiredAt);

        $result = [
            'display' => Configuration::getItem('captcha') ? true : false,
            //'verification_display' => Configuration::getItem('verification') ? true : false,
            'captcha_key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
            'captcha_image_content' => $captcha->inline()
        ];
        return $this->success($result);
    }
}
