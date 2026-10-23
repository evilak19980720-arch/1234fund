<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class OrderOverdue extends Command
{
    protected $signature = 'orderoverdue';

    protected $description = '订单过期';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Order::where('type', '=', 1)->where('closed', '=', 0)->where('state', '=', 0)->where('created_at', '<', Carbon::now()->subHours(72))->update(['closed' => 1,'state'=>2]);
    }
}
