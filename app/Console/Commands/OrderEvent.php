<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class OrderEvent extends Command
{
    protected $signature = 'orderevent';

    protected $description = '超时重新推送';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $orders = Order::query()
            ->where('type',1)
            ->where('state',Order::SUCCESS)
            ->where('isfirstRecharge',1)
            ->where('isevent', '=', 0)
            ->get();
        foreach ($orders as $order){
            Order::firstRechargeSuccess($order->user_id , $order->amount, $order->no);
        }
    }
}
