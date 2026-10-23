<?php

namespace App\Jobs;

use App\Models\Order;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Models\UserRechargeAddress;
use App\Handlers\KeysHandler;

/**
 * 查询TRC交易订单状态
 * @package App\Jobs
 */
class Urecharge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user_id;
    protected $attach;

    public function __construct($user_id ,$attach)
    {
        $this->user_id = $user_id;
        $this->attach = $attach;
    }

    public function handle()
    {
            app(UserRechargeAddress::class)->checkRecharge($this->user_id , $this->attach);

    }
}
