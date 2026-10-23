<?php


namespace App\Console\Commands;


use App\Jobs\AutoSynthesizeQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoSynthesizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AutoSynthesize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动合成宠物';

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
        //查询待合成数据
        $pets = DB::table('pets')->whereRaw('waiting_quantity > 1 and is_auto=1')->get(['id']);
        if ($pets->isNotEmpty()) {
            foreach ($pets as $pet) {
                AutoSynthesizeQueue::dispatch($pet)->onQueue(env('REDIS_QUEUE', 'default'));
            }
        }
    }


}
