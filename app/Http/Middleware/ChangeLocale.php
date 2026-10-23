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
        $language = $request->header('accept-language');
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
}
