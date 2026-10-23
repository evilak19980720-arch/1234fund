<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckWithdrawalDeviceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:withdrawal-device';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查待审核提现记录的设备有效性';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('开始检查待审核提现记录的设备有效性...');

        // 获取所有待审核的提现记录
        $orders = Order::query()
            ->where('type', Order::WITHDRAWAL)
            ->where('state', Order::REVIEW)
            ->where('closed', 0)
            ->with('user')
            ->get();

        $this->info("找到 {$orders->count()} 条待审核提现记录");

        $checkedCount = 0;
        $refusedCount = 0;

        foreach ($orders as $order) {
            $checkedCount++;

            // 获取用户设备号
            $user = $order->user;
            if (!$user || empty($user->device_number)) {
                $this->warn("订单 {$order->no} 的用户没有设备号，跳过");
                continue;
            }

            // 检查设备有效性
            $isValid = checkDeviceValid($user->device_number);

            if ($isValid === false) {
                // 设备无效，拒绝提现
                $this->refuseWithdrawal($order);
                $refusedCount++;
                $this->info("订单 {$order->no} 设备无效，已拒绝提现");
            } elseif ($isValid === null) {
                // 接口调用失败，记录日志但不处理
                $this->warn("订单 {$order->no} 设备检查接口调用失败，跳过");
            }

            // 每次请求后限频1秒
            sleep(1);
        }

        $this->info("检查完成：共检查 {$checkedCount} 条，拒绝 {$refusedCount} 条");

        return 0;
    }



    /**
     * 拒绝提现
     *
     * @param Order $order
     * @return void
     */
    private function refuseWithdrawal($order)
    {
        DB::transaction(function () use ($order) {
            // 锁定订单
            $order = Order::where('id', $order->id)
                ->where('state', Order::REVIEW)
                ->lockForUpdate()
                ->first();
            if (!$order) {
                // 订单已被处理，跳过
                return;
            }
            // 更新订单状态
            $order->state = Order::REFUSE;
            $order->closed = 1;
            $order->remark = 'Fake user';
            $order->save();
            // 减少冻结金额，增加余额
            User::where('id', $order->user_id)->decrement('frozen', $order->amount);

        });
    }
}

