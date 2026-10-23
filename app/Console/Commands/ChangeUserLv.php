<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Vip;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChangeUserLv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ChangeUserLv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新用户vip等级';

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
        $this->line('开始执行');
        $vips = Vip::all();
        User::query()->chunk(300,function($users) use ($vips){
            foreach ($users as $user)
            {
                $charge_amount = DB::table('orders')->where('type','=',1)
                    ->where('state','=',1)
                    ->where('user_id','=',$user->id)
                    ->sum('amount');
                if (!$charge_amount)
                {
                    continue;
                }
                $new_vip_id = 1;
                foreach ($vips as $vip)
                {
                    if($charge_amount >= $vip->recharge_amount)
                    {
                        $new_vip_id = $vip->id;
                    }
                }
                User::query()->where('id','=',$user->id)->update(['vip_id' => $new_vip_id]);
            }
        });
        $this->info('执行完成');
    }
}
