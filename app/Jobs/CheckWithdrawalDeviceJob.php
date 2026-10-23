<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 检查提现订单的设备有效性
 * @package App\Jobs
 */
class CheckWithdrawalDeviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;

    /**
     * Create a new job instance.
     *
     * @param int $orderId 订单ID
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 限频1秒
        sleep(1);

        // 获取订单
        $order = Order::with('user')->find($this->orderId);

        if (!$order) {
            Log::warning('检查设备有效性：订单不存在', ['order_id' => $this->orderId]);
            return;
        }

        // 检查订单状态，只处理待审核的提现订单
        if ($order->type !== Order::WITHDRAWAL || $order->state !== Order::REVIEW || $order->closed !== 0) {
            Log::info('检查设备有效性：订单状态不符合要求，跳过', [
                'order_id' => $this->orderId,
                'order_no' => $order->no,
                'type' => $order->type,
                'state' => $order->state,
                'closed' => $order->closed
            ]);
            return;
        }

        // 获取用户设备号
        $user = $order->user;
        if (!$user || empty($user->device_number)) {
            Order::refuseWithdrawal($order);
            Log::info('检查设备有效性：用户没有设备号，已拒绝提现', [
                'order_id' => $this->orderId,
                'order_no' => $order->no,
                'user_id' => $order->user_id
            ]);
            return;
        }

        $lastRecharge = Account::where('to', '=', $user->id)->where('type', '=', Account::RECHARGE)->where('order_id', '<>', 0)->orderBy('id', 'desc')->first();
        if($lastRecharge){
            return;
        }
        // 检查设备有效性
        $isValid = checkDeviceValid($user->device_number);

        if ($isValid === false) {
            // 设备无效，拒绝提现
            Order::refuseWithdrawal($order);
            Log::info('检查设备有效性：设备无效，已拒绝提现', [
                'order_id' => $this->orderId,
                'order_no' => $order->no,
                'user_id' => $order->user_id,
                'device_number' => $user->device_number
            ]);
        } elseif ($isValid === null) {
            // 接口调用失败，记录日志但不处理
            Log::warning('检查设备有效性：接口调用失败', [
                'order_id' => $this->orderId,
                'order_no' => $order->no,
                'user_id' => $order->user_id,
                'device_number' => $user->device_number
            ]);
            \App\Jobs\CheckWithdrawalDeviceJob::dispatch($order->id)->delay(60)->onQueue(env('REDIS_QUEUE', 'default'));

        }
    }


}

