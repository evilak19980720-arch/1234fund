<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Http\Request;

class ThrottleAuthenticated extends ThrottleRequests
{
    /**
     * Handle an incoming request.
     * 只对已登录用户进行限流，未登录用户不受限制
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|string  $maxAttempts
     * @param  float|int  $decayMinutes
     * @param  string  $prefix
     * @return mixed
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        // 如果用户未登录，直接通过，不进行限流
        if (!$request->user()) {
            return $next($request);
        }

        // 用户已登录，执行限流逻辑
        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }
}

