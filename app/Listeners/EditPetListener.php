<?php

namespace App\Listeners;

use App\Admin\Models\Pet;
use App\Events\EditPetEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class EditPetListener implements ShouldQueue
{

    /**
     * 任务将被发送到的队列的名称
     * @var mixed
     */
    public $queue;

    public $pet;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Pet $pet)
    {
        $this->pet = $pet;
        $this->queue = env('REDIS_QUEUE', 'default');
    }

    /**
     * Handle the event.
     *
     * @param  EditPet  $event
     * @return void
     */
    public function handle(EditPetEvent $event)
    {
        $this->pet->PreFishAndCommission($event->pet);
    }
}
