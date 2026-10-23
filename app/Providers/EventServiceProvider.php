<?php

namespace App\Providers;

use App\Events\BuyPetSuccessEvent;
use App\Events\EditPetEvent;
use App\Events\UserRechargeEvent;
use App\Events\UserRegisterEvent;
use App\Listeners\EditPetListener;
use App\Listeners\UserRechargeListener;
use App\Listeners\UserRegisterListener;
use App\Listeners\VipUpgradeListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        EditPetEvent::class => [
            EditPetListener::class,
        ],
        //用户注册事件
        UserRegisterEvent::class => [
            UserRegisterListener::class,
        ],
        //用户充值成功事件
        UserRechargeEvent::class => [
            UserRechargeListener::class,
        ],
        //用户购买宠物成功事件
        BuyPetSuccessEvent::class => [
            //vip升级
            VipUpgradeListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }







}
