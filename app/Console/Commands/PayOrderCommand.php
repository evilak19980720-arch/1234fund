<?php


namespace App\Console\Commands;


use App\Jobs\PayOrder;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PayOrderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '查询支付订单';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        //Log::error('time'.date('Y-m-d H:i:s'));

        /*
        for ($i=1;$i<=2;$i++){
            dispatch(new PayOrder());
            sleep(30);
        }
     */
    }


}
