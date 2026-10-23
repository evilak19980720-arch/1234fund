<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Carbon\Carbon;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }

    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);
        $user = $request->user();
        if ($user->forbid) {
            $key = sprintf('%s_%d_%s', 'user', $user->id, 'token');
            $oldToken = Cache::get($key);
            if ($oldToken) {
                $this->auth->setToken($oldToken);
                if ($this->auth->check()) {
                    try {
                        $this->auth->invalidate();
                    } catch (TokenExpiredException $e) {
                    }
                }
            }
            throw new AuthenticationException();
        }
        Cache::put('user_updated_at_'.$user->id,1,3600*25);
        return $next($request);
    }


    /*
     * {
	"code": 401,
	"msg": "unauthorized",
	"data": null
}
     * */
    protected function unauthenticated($request, array $guards)
    {

        throw new HttpException(Response::HTTP_OK, 'Please login again', null, [], 401);
        //throw new ApiException('未登录',401);
    }
}
