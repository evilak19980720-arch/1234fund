<?php

namespace App\Jobs;


use App\Models\UserRechargeAddress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

/**
 * 查询余额
 * @package App\Jobs
 */
class CheckBscBalance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $address;

    public function __construct($address)
    {
        $this->address = $address;
    }

    public function handle()
    {
        sleep(1);
        UserRechargeAddress::bsc20BalanceOf($this->address);
    }



}
