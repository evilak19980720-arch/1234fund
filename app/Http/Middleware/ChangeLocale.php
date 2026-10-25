<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ChangeLocale
{
    public function handle($request, Closure $next)
    {
        $language = $this->language((string) $request->header('accept-language'));
        //if ($language && in_array($language,config('app.locale_list'))) {
        if ($language) {
            App::setLocale($language);
        }else{
            $language = config('app.api_locale');
            App::setLocale($language);
        }

        // 用随机字符串验证重复请求
        $str = $request->header('random-string');
        if(!empty($str)){
            $key = 'randomStrings:'.$str;
            if (Cache::has($key)){
                abort(429, 'Frequent requests');
            }
            Cache::put($key, '1' , 120);
        }

        return $next($request);
    }

    /**
     * 前端传的是 en、zh 这样的语言代码，原样使用；
     * 浏览器直接访问时是 "en-US,en;q=0.9" 这种格式，取第一个语言，否则 setLocale 会抛异常导致 500
     */
    protected function language(string $header): string
    {
        $language = trim(explode(';', explode(',', $header)[0])[0]);

        return preg_match('/^[A-Za-z0-9_-]{1,20}$/', $language) ? $language : '';
    }
}
