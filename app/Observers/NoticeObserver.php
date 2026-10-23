<?php

namespace App\Observers;

use App\Handlers\KeysHandler;
use App\Models\Notice;
use Illuminate\Support\Facades\Cache;

class NoticeObserver
{
    public function saved(Notice $notice)
    {
        Cache::forget(sprintf(KeysHandler::NOTICE,$notice->id));
        Cache::forget(sprintf(KeysHandler::NOTICES,$notice->admin_user_id));
        Cache::forget(sprintf(KeysHandler::TOP_NOTICE,$notice->admin_user_id));
        Cache::forget(sprintf(KeysHandler::EJECT_NOTICE,$notice->admin_user_id));
    }

    public function deleted(Notice $notice)
    {
        Cache::forget(sprintf(KeysHandler::NOTICE,$notice->id));
        Cache::forget(sprintf(KeysHandler::NOTICES,$notice->admin_user_id));
        Cache::forget(sprintf(KeysHandler::TOP_NOTICE,$notice->admin_user_id));
        Cache::forget(sprintf(KeysHandler::EJECT_NOTICE,$notice->admin_user_id));
    }
}
