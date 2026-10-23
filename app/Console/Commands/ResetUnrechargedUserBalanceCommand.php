<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetUnrechargedUserBalanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:unrecharged-user-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将注册时间在今天之前的、没有充值的会员余额全部清零';

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
        $today = Carbon::today()->startOfDay();

        $this->info("开始查找注册时间在 {$today->toDateString()} 之前且没有充值的用户...");

        // 先获取有成功充值订单的用户ID列表
        $rechargedUserIds = Order::query()
            ->where('type', Order::RECHARGE)
            ->where('state', Order::SUCCESS)
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        // 查找注册时间在今天之前、没有成功充值记录、且余额大于0的用户
        $query = User::query()
            ->where('created_at', '<', $today)
            ->where('balance', '>', 0);

        if (!empty($rechargedUserIds)) {
            $query->whereNotIn('id', $rechargedUserIds);
        }

        $users = $query->get();
        $count = $users->count();

        if ($count === 0) {
            $this->info('没有找到符合条件的用户。');
            return 0;
        }

        $this->info("找到 {$count} 个符合条件的用户，准备清零余额...");

        $totalBalance = 0;
        $successCount = 0;

        // 使用事务批量更新
        DB::transaction(function () use ($users, &$totalBalance, &$successCount) {
            foreach ($users as $user) {
                if ($user->balance > 0) {
                    $totalBalance += $user->balance;
                    $user->balance = 0;
                    $user->save();
                    $successCount++;
                }
            }
        });

        // 余额单位可能是分，除以100转换为元显示
        $totalBalanceInYuan = $totalBalance / 100;
        $this->info("成功清零 {$successCount} 个用户的余额，累计清零金额: {$totalBalanceInYuan} 元");

        return 0;
    }
}
