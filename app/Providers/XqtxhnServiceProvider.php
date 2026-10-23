<?php

namespace App\Providers;

use App\Services\Sms\XqtxhnService;
use Illuminate\Support\ServiceProvider;

class XqtxhnServiceProvider extends ServiceProvider
{
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
        $this->app->singleton(XqtxhnService::class, function ($app) {
            return new XqtxhnService(config('sms.xqtxhn'));
        });

        $this->app->alias(XqtxhnService::class, 'xqtxhn');
    }
}
