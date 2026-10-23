<?php

namespace App\Console\Commands;

use App\Handlers\KeysHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AddFakeThisWeekCommissionTopFiveCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fake:add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '给假数据的佣金增加';

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
        $fake = Cache::get(KeysHandler::FAKE_THIS_WEEK_COMMISSION_TOP_FIVE);
        if ($fake) {
            $fake = $fake->map(function ($item) use ($fake) {
                $item['commission'] = ($item['commission'] / 100 + rand(100, 500)) * 100;
                $item['subordinate'] += rand(0, 2);
                return $item;
            });

            $fake = $fake->sortByDesc('commission');
            Cache::put(KeysHandler::FAKE_THIS_WEEK_COMMISSION_TOP_FIVE, $fake, now()->endOfWeek());
        }
    }
}
