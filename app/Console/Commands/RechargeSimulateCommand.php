<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\Order;
use App\Models\User;
use App\Models\UserRechargeAddress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 链上充值入账模拟测试：用假交易号走一遍入账逻辑，全部在事务里执行，结束后回滚，不改任何真实数据
 * 不推送通知、不触发充值事件、不调用区块链接口
 */
class RechargeSimulateCommand extends Command
{
    protected $signature = 'recharge:simulate {user_id : 用来测试的用户 ID（建议用测试账号）} {--currery=USDT-TRC}';

    protected $description = '模拟链上充值入账（防重复入账、旧交易、低于最低金额、匹配待支付订单），结束后全部回滚';

    protected $results = [];

    public function handle()
    {
        $userId = (int) $this->argument('user_id');
        $currery = (string) $this->option('currery');

        if (!User::query()->whereKey($userId)->exists()) {
            $this->error("用户 {$userId} 不存在");
            return 1;
        }

        // 只有 InnoDB 表才能回滚，否则模拟会留下真实数据
        $tables = array_map(fn ($t) => DB::getTablePrefix() . $t, ['users', 'orders', 'accounts', 'chain_deposits']);
        $engines = collect(DB::select(
            'SELECT TABLE_NAME AS name, ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (' . implode(',', array_fill(0, count($tables), '?')) . ')',
            $tables
        ))->pluck('engine', 'name');
        foreach ($tables as $table) {
            if (!isset($engines[$table])) {
                $this->error("缺少数据表 {$table}，请先执行 chain_deposits 迁移");
                return 1;
            }
            if (strtolower((string) $engines[$table]) !== 'innodb') {
                $this->error("数据表 {$table} 是 {$engines[$table]} 引擎，不支持事务回滚，已停止模拟");
                return 1;
            }
        }

        $min = (int) Configuration::getItem('recharge_min');
        $amount = max($min, 100) + 123; // 分
        $balanceBefore = $this->balance($userId);
        $prefix = 'simtest' . time();
        $model = app(UserRechargeAddress::class);

        DB::beginTransaction();
        try {
            // 1. 有金额相同的待支付订单：应更新这笔订单为成功，并加余额
            $pending = $this->pendingOrder($userId, $amount, $currery);
            $tx1 = $prefix . 'a';
            $order = $model->creditDeposit($userId, $tx1, $amount, $amount, $currery, ['simulate' => true]);
            $this->check('匹配待支付订单并入账', $order && $order->id === $pending->id && (int) $order->state === Order::SUCCESS);
            $this->check('余额增加 ' . $amount / 100, $this->balance($userId) === $balanceBefore + $amount);
            $this->check('写入充值账变', Account::query()->where('order_id', $pending->id)->where('type', Account::RECHARGE)->where('amount', $amount)->exists());
            $this->check('交易登记为 credited', $this->depositStatus($tx1) === 'credited');

            // 2. 同一笔交易再查一次（模拟重复查询 / 并发任务）：不应再入账
            $again = $model->creditDeposit($userId, $tx1, $amount, $amount, $currery, ['simulate' => true]);
            $this->check('同一交易重复处理不入账', $again === null && $this->balance($userId) === $balanceBefore + $amount);

            // 3. 交易号大小写不同（BSC 哈希）也算同一笔
            $upper = $model->creditDeposit($userId, strtoupper($tx1), $amount, $amount, $currery, ['simulate' => true]);
            $this->check('交易号大小写不同也不重复入账', $upper === null && $this->balance($userId) === $balanceBefore + $amount);

            // 4. 没有待支付订单：应新建一笔成功订单并入账
            $tx2 = $prefix . 'b';
            $order2 = $model->creditDeposit($userId, $tx2, $amount, $amount, $currery, ['simulate' => true]);
            $this->check('无待支付订单时新建订单入账', $order2 && $order2->id !== $pending->id && $order2->payment_no === $tx2);
            $this->check('余额累计增加 ' . $amount * 2 / 100, $this->balance($userId) === $balanceBefore + $amount * 2);

            // 5. 改造前已经入账的交易（订单里已有这个交易号）：不应再入账
            $tx3 = $prefix . 'c';
            $this->legacyOrder($userId, $amount, $currery, $tx3);
            $legacy = $model->creditDeposit($userId, $tx3, $amount, $amount, $currery, ['simulate' => true]);
            $this->check('改造前已入账的交易不重复入账', $legacy === null && $this->depositStatus($tx3) === 'existing' && $this->balance($userId) === $balanceBefore + $amount * 2);

            // 6. 低于最低充值金额：不入账，但留一笔失败订单
            if ($min > 1) {
                $tx4 = $prefix . 'd';
                $small = $model->creditDeposit($userId, $tx4, $min - 1, $min - 1, $currery, ['simulate' => true]);
                $failed = Order::query()->where('payment_no', $tx4)->first();
                $this->check('低于最低金额不入账', $small === null && $this->balance($userId) === $balanceBefore + $amount * 2);
                $this->check('低于最低金额留下失败订单', $failed && (int) $failed->state === Order::FAILED && $this->depositStatus($tx4) === 'below_min');
            } else {
                $this->results[] = ['低于最低金额', '跳过（后台最小充值金额未设置）'];
            }
        } catch (\Throwable $e) {
            $this->results[] = ['执行出错', $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine()];
        } finally {
            DB::rollBack();
        }

        // 回滚后数据应完全恢复
        $this->check('回滚后余额恢复原值', $this->balance($userId) === $balanceBefore);
        $this->check('回滚后没有残留测试数据', !DB::table('chain_deposits')->where('tx_id', 'like', $prefix . '%')->exists()
            && !Order::query()->where('payment_no', 'like', $prefix . '%')->exists());

        $this->table(['检查项', '结果'], $this->results);
        $failed = collect($this->results)->filter(fn ($row) => $row[1] !== 'PASS')->count();
        $failed ? $this->error("{$failed} 项未通过") : $this->info('全部通过，测试数据已回滚');

        return $failed ? 1 : 0;
    }

    protected function check(string $name, bool $ok)
    {
        $this->results[] = [$name, $ok ? 'PASS' : 'FAIL'];
    }

    protected function balance(int $userId): int
    {
        return (int) User::query()->whereKey($userId)->value('balance');
    }

    protected function depositStatus(string $txId): ?string
    {
        return DB::table('chain_deposits')->where('tx_id', strtolower($txId))->value('status');
    }

    protected function pendingOrder(int $userId, int $amount, string $currery): Order
    {
        $order = new Order();
        $order->platform = $currery;
        $order->payment_id = 0;
        $order->user_id = $userId;
        $order->amount = $amount;
        $order->actual_amount = $amount;
        $order->no = only_code();
        $order->type = Account::RECHARGE;
        $order->save();
        return $order->refresh();
    }

    protected function legacyOrder(int $userId, int $amount, string $currery, string $txId): Order
    {
        $order = $this->pendingOrder($userId, $amount + 1, $currery);
        $order->state = Order::SUCCESS;
        $order->closed = 1;
        $order->payment_no = $txId;
        $order->save();
        return $order;
    }
}
