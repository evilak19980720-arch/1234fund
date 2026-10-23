<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Interest;
use App\Models\InterestLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InterestWithdrawal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'interestWithdrawal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '平台会员利息宝转出';

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
     */
    public function handle()
    {
        Interest::query()->whereHas('user',function($sql){
            $sql->where('admin_user_id','=',0);
        })->where('balance','>',0)
            ->chunk(200,function($data){
            foreach ($data as $item)
            {
                DB::transaction(function () use ($item) {
                    $interest = Interest::query()->where('user_id', '=', $item->user_id)->sharedLock()->first();
                    // 利息宝-钱
                    $amount = $interest->balance;
                    $interest->balance = $interest->balance - $amount;
                    $interest->confirm_balance = 0;
                    $interest->update();
                    // 利息宝-记账
                    InterestLog::query()->create([
                        'user_id' => $item->user_id,
                        'amount' => 0 - $amount,
                        'balance' => $interest->balance,
                        'confirm' => 1
                    ]);

                    // 余额+钱
                    User::query()->where('id', '=',$item->user_id)->increment('balance', $amount);
                    // 余额-记账
                    Account::query()->create([
                        'type' => Account::WINTEREST,
                        'to' => $item->user_id,
                        'from' => 0,
                        'amount' => $amount,
                        'level' => 0
                    ]);
                });
            }
        });
        $this->info('执行完成');
    }
}
