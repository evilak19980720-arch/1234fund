<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\UserRechargeAddress;
use App\Services\Pay\PayFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use App\Handlers\KeysHandler;

/**
 * 支付订单
 * @package App\Jobs
 */
class PayOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $attach;

    public function __construct(Order $order, $attach)
    {
        $this->order = $order;
        $this->attach = $attach;
    }

    public function handle()
    {

            switch ($this->order->type) {
                case 0:
                    $this->withdrawal();
                    break;
                case 1:
                    $this->recharge();
                    break;
            }

    }

    // 充值
    private function recharge()
    {

    }

    // 提现
    private function withdrawal()
    {
        PayFactory::service(ucfirst($this->order->platform),$this->order)->withdrawal($this->attach);
    }
}
