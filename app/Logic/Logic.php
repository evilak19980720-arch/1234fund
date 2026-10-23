<?php


namespace App\Logic;



use Illuminate\Support\Carbon;

class Logic
{
    protected $error = 0;

    protected function cache24Hour()
    {
        return 60 * 60 * 24;
    }

    protected function cacheToday()
    {
        return Carbon::tomorrow()->timestamp - Carbon::now()->timestamp;
    }
}
