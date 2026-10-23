<?php


namespace App\Services\Pay;


/**
 * 支付
 * 抽象层
 * @package App\Services
 */
interface Pay
{
    // 充值
    public function recharge();
    // 提现
    public function withdrawal();
}
