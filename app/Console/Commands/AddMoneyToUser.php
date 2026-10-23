<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddMoneyToUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addMoneyToUser';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '给指定代理商所有用户加10000分，流水为系统充值';

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
        $admin_user_id = 10;
        $this->info('一共'.User::query()->where('admin_user_id','=',$admin_user_id)->where('created_at','>=','2021-04-27 12:02:41')->count().'名会员需要处理');
        $money = 10000;
        User::query()->where('admin_user_id','=',$admin_user_id)
            ->where('created_at','>=','2021-04-27 12:02:41')
            ->whereNotIn('id',[])
            ->oldest()
            ->chunk(300,function ($users) use($money){
                foreach ($users as $user)
                {
                    DB::transaction(function() use ($user,$money){
                        //增加用户余额
                        $user->increment('balance',$money);
                        //增加流水
                        Account::query()->create([
                            'type' => Account::SYSTEM_RECHARGE,
                            'to' => $user->id,
                            'from' => 0,
                            'amount' => $money,
                            'level' => 0
                        ]);
                    });
                }
            });
        $this->info('处理完成');
    }
}
