<?php


namespace App\Observers;


use App\Handlers\KeysHandler;
use App\Models\Pet;
use Illuminate\Support\Facades\Cache;

class PetObserver
{
    public function saved(Pet $pet)
    {
        Cache::forget(KeysHandler::PETS);
        $key = sprintf(KeysHandler::PET, $pet->id);
        Cache::forget($key);
    }

    public function deleted(Pet $pet)
    {
        Cache::forget(KeysHandler::PETS);
        $key = sprintf(KeysHandler::PET, $pet->id);
        Cache::forget($key);
    }
}
