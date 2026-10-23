<?php

namespace App\Providers;

use App\Service\Pay\PayService;
use Illuminate\Support\ServiceProvider;

class PayServiceProvider extends ServiceProvider
{
    public $payWay = [1 => 'wechat', 2 => 'ali'];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind('pay', function ($app, $params = []) {
            $params['pay_way'] = $this->payWay[$params['pay_way']??1];
            return new PayService($params);

        });
    }
}
