<?php


namespace App\Console\Commands;


use App\Jobs\AutoSynthesizeQueue;
use App\Models\Pet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoDecrementCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AutoDecrement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动减库存';

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
        $pets = DB::table('pets')->whereRaw('buying_price <= 1000000 and stock_quantity>0')->get();
        if ($pets->isNotEmpty()) {
            foreach ($pets as $pet) {
                if ($pet->stock_quantity >= 4) {
                    Pet::where('id', $pet->id)->decrement('stock_quantity', mt_rand(1, 3));
                }
            }
        }
    }


}
