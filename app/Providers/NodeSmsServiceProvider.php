<?php

namespace App\Providers;

use App\Services\Sms\NodeSmsService;
use Illuminate\Support\ServiceProvider;

class NodeSmsServiceProvider extends ServiceProvider
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
        $this->app->singleton(NodeSmsService::class, function ($app) {
            return new NodeSmsService(config('sms.nodeSms'));
        });

        $this->app->alias(NodeSmsService::class, 'nodeSms');
    }
}
