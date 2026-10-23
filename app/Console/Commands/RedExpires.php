<?php

namespace App\Console\Commands;

use App\Models\Red;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RedExpires extends Command
{
    protected $signature = 'redExpires';

    protected $description = '红包过期';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Red::where('state', '=', 1)->where('expires_at', '<', Carbon::now())->update(['closed' => 1,'state'=>4]);
    }
}
