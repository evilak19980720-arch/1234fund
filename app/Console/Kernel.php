<?php

namespace App\Console;

use App\Console\Commands\AutoDecrementCommand;
use App\Console\Commands\AutoSynthesizeCommand;
use App\Console\Commands\PayOrderCommand;
use App\Console\Commands\SettleAccountsCommand;
use App\Console\Commands\VipDueDateCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        PayOrderCommand::class,
        VipDueDateCommand::class,
        SettleAccountsCommand::class,
        AutoSynthesizeCommand::class,
        AutoDecrementCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command('AutoDecrement')->hourly();//每小时自动减库存
        //$schedule->command('orderoverdue')->hourly();
        //$schedule->command('Interest')->at('02:10'); //利息宝 定时开始计算收益
        //$schedule->command('period:generate')->dailyAt('23:00');//每天生成新彩种排期
        //$schedule->command('record:phone')->dailyAt('04:50');
        $schedule->command('resetUserStatisticTodayInfo')->daily();//每日 0 点执行一次，把用户统计中的今日信息重置为0

        if(config('app.revenue_model') == 'day'){
            $schedule->command('SellDay')->at('12:00'); //自动卖出收益
        }else{
            $schedule->command('recycle:pet')->everyTenMinutes();//自动回收
        }

        $schedule->command('orderevent')->everyTenMinutes();//超时重新推送事件
        $schedule->command('recharge:scan')->everyFiveMinutes()->withoutOverlapping(30)->runInBackground();//补查链上充值到账
       // $schedule->command('fake:add')->hourly();//每小时给排名假数据增加值
        //$schedule->command('salesvolume:add')->everyTenMinutes();//每小时给排名假数据增加值

        //$schedule->command('redExpires')->everyMinute();  //红包自动过期
        $schedule->command('AgencyContestFakeUser')->everyFourHours(); //everyTwoHours();//代理争霸假会员自动加数量
        $schedule->command('AgencyContestEnd')->everyMinute(); //everyTwoHours();//代理争霸结束

        //$schedule->command('telegram:crond')->everyMinute();

    }

    /*
     * 秒级定时任务
     * 守护进程执行 php artisan short-schedule:run
     * */
    protected function shortSchedule(\Spatie\ShortSchedule\ShortSchedule $shortSchedule)
    {
        // this artisan command will run every second
        //$shortSchedule->command('artisan-command')->everySecond();
        //$shortSchedule->command('payOrder')->everySecond();
        //$shortSchedule->command('artisan-command')->everySeconds(30);
        //$shortSchedule->command('artisan-command')->everySeconds(0.5);
        //$shortSchedule->command('artisan-command')->everySecond()->onOneServer(); 只在一台服务器执行
        //$shortSchedule->command('artisan-command')->everySecond()->withoutOverlapping();  禁止重叠执行
        //$shortSchedule->command('issue:clean')->everySeconds(11);
    }


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
