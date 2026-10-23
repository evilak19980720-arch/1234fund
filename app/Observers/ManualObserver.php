<?php

namespace App\Observers;

use App\Models\Manual;

class ManualObserver
{
    public function saved(Manual $manual)
    {
        Manual::clean($manual->id);
        Manual::clean();
    }

    public function deleted(Manual $manual)
    {
        Manual::clean($manual->id);
        Manual::clean();
    }
}
