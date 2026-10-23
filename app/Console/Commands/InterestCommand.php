<?php


namespace App\Console\Commands;


use App\Jobs\InterestQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InterestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Interest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '利息宝 定时开始计算收益';

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
        $interests = DB::table('interests')->select('user_id')->get();
        foreach ($interests as $val){
            InterestQueue::dispatch($val->user_id)->onQueue(env('REDIS_QUEUE', 'default'));
        }
    }


}
