<?php


namespace App\Console\Commands;


use App\Jobs\SellDayQueue;
use App\Models\PetOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SellDayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SellDay';

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
        if(config('app.revenue_model') != 'day'){
            return;
        }

        //查询已购买的并且未出售的 type=1 , state=0
        PetOrder::query()
            ->whereRaw('`day`>`sell_day` and type=1 and state=0')
            ->whereDate('start_at','<', Carbon::now())
            ->chunkById(100, function ($pet_orders) {
                foreach ($pet_orders as $pet_order) {
                    SellDayQueue::dispatch($pet_order)->onQueue(env('REDIS_QUEUE_FAST', 'default'));
                }
            });
    }


}
