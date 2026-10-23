<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\Configuration;
use Closure;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class IP
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $ip = $request->ip();
        if (config('APP_DEBUG') === false) {
            $code = geoip_country_code_by_name($ip);
            if (!in_array($code, explode(',', Configuration::getItem('ip_area_white_list')))) {
                throw new AccessDeniedHttpException('access is not allowed in this area');
            }
        }
        return $next($request);
    }
}
