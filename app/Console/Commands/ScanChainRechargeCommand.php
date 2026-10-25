<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\UserRechargeAddress;
use Illuminate\Console\Command;

/**
 * 补查链上充值：用户转账后没点「我已付款」、或 10 分钟查询窗口内还没确认的，由这里定时补上
 */
class ScanChainRechargeCommand extends Command
{
    const CURRERIES = ['USDT-TRC', 'USDT-BSC', 'TRX'];

    protected $signature = 'recharge:scan {--hours=24 : 只查这么多小时内有待支付订单的用户} {--limit=200 : 每次最多查询的地址数}';

    protected $description = '扫描最近有待支付链上充值订单的用户地址，自动补入账';

    public function handle()
    {
        $rows = Order::query()
            ->where('type', Order::RECHARGE)
            ->where('state', Order::PENDING)
            ->whereIn('platform', self::CURRERIES)
            ->where('created_at', '>=', now()->subHours((int) $this->option('hours')))
            ->select('user_id', 'platform')
            ->distinct()
            ->limit((int) $this->option('limit'))
            ->get();

        $model = app(UserRechargeAddress::class);
        foreach ($rows as $row) {
            try {
                $model->scanTransactions($row->user_id, $row->platform);
            } catch (\Throwable $e) {
                info('链上充值补查失败', ['user_id' => $row->user_id, 'currery' => $row->platform, 'message' => $e->getMessage()]);
            }
            // 避免触发 TronGrid / NodeReal 限频
            usleep(300000);
        }

        $this->info('checked ' . $rows->count() . ' address(es)');
        return 0;
    }
}
