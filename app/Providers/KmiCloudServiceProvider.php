<?php

namespace App\Providers;

use App\Services\Sms\KmiCloudSmsService;
use Illuminate\Support\ServiceProvider;

class KmiCloudServiceProvider extends ServiceProvider
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
        $this->app->singleton(KmiCloudSmsService::class, function ($app) {
            return new KmiCloudSmsService(config('sms.kmiCloud'));
        });

        $this->app->alias(KmiCloudSmsService::class, 'kmiCloud');
    }
}
