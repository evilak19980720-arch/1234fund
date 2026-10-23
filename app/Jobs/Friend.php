<?php

namespace App\Jobs;

use App\Models\Friend as FriendModel;
use App\Models\State;
use App\Services\Factory;
use App\Services\ShineuPayService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 圈子自动点赞
 * @package App\Jobs
 */
class Friend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function handle()
    {
        FriendModel::where('id','=',$this->id)->increment('times',mt_rand(104,999));
    }
}
