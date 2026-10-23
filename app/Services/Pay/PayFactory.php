<?php


namespace App\Services\Pay;


class PayFactory
{
    public static function service($provider,$order)
    {
        $class = 'App\\Services\\Pay\\' . sprintf('%sPayService', $provider);
        if (class_exists($class)) {
            return new $class($order);
        }
    }
}
