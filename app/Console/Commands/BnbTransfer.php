<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\BscService;

class BnbTransfer extends Command
{

    protected $signature = 'bnbtransfer';

    protected $description = 'BNB转账';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $from = "0xd99572f836f75e3cca32f989149699ab1480aeee";
        $private = config('services.bnb_transfer.private_key');
        $amount = 0.00003;
        $toadd = [

        ];
        foreach ($toadd as $to_address){
            $return = app(BscService::class)->transferbnb($to_address, $amount ,$from, $private);
            sleep(4);
            $this->info($to_address);
            $this->info($return);
        }


    }
}
