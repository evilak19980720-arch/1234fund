<?php

namespace App\Listeners;

use App\Events\BuyPetSuccessEvent;
use App\Models\User;
use App\Models\Vip;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class VipUpgradeListener implements ShouldQueue
{

    public $delay = 2;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param BuyPetSuccessEvent $event
     * @return void
     */
    public function handle(BuyPetSuccessEvent $event)
    {
        /*
        if ($event->user->relation) {
            //处理一下邀请关系，查询出上级
            $user_ids = explode(',', trim($event->user->relation, ','));
            $parent_id = array_pop($user_ids);
            $user = User::find($parent_id);
            app(Vip::class)->upgrade($user);
        }
        */
        if (!empty($event->user->parent_id)) {
            $user = User::find($event->user->parent_id);
            app(Vip::class)->upgrade($user);
        }


    }
}
