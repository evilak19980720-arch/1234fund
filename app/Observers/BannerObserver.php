<?php

namespace App\Observers;

use App\Handlers\KeysHandler;
use App\Models\Banner;
use Illuminate\Support\Facades\Cache;

class BannerObserver
{
    public function saved(Banner $banner)
    {
        Cache::forget(sprintf(KeysHandler::BANNER,0));
        Banner::getBanner(0);
        Cache::forget(sprintf(KeysHandler::BANNER,1));
        Banner::getBanner(1);
        Cache::forget(sprintf(KeysHandler::BANNER,2));
        Banner::getBanner(2);

    }

    public function deleted(Banner $banner)
    {
        Cache::forget(sprintf(KeysHandler::BANNER,0));
        Banner::getBanner(0);
        Cache::forget(sprintf(KeysHandler::BANNER,1));
        Banner::getBanner(1);
        Cache::forget(sprintf(KeysHandler::BANNER,2));
        Banner::getBanner(2);
    }
}
