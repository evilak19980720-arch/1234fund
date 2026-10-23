<?php


namespace App\Console\Commands;


use App\Jobs\InterestQueue;
use App\Jobs\SettleAccountsQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SettleAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SettleAccounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '周期结算';

    /**
     * Create a new command instance.e
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        //   已改为用户手动卖出，无需自动结算
        //查询已购买的并且未出售的 type=1 , state=0
//        $pet_orders = DB::table('pet_orders')
//            ->whereRaw('datediff(curdate() ,`start_at`)>=`day` and type=1 and state=0')
//            ->get(['id']);
//        if ($pet_orders->isNotEmpty()) {
//            foreach ($pet_orders as $pet_order) {
//                SettleAccountsQueue::dispatch($pet_order)->onQueue(env('REDIS_QUEUE', 'default'));
//            }
//        }
    }


}
