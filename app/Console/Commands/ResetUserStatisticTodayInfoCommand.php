<?php

namespace App\Console\Commands;

use App\Models\UserStatistic;
use Illuminate\Console\Command;

class ResetUserStatisticTodayInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resetUserStatisticTodayInfo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重置用户统计表今日充值信息跟下级今日充值总额，每日凌晨执行一次即可';

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
        UserStatistic::query()->update([
            'today_recharge' => 0,
            'subordinate_today_recharge' => 0
        ]);
    }
}
