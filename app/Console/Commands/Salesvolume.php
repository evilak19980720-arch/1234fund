<?php

namespace App\Console\Commands;

use App\Models\Pet;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class Salesvolume extends Command
{
    protected $signature = 'salesvolume:add';

    protected $description = '每小时自动增加销售数量';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $pets = Pet::all();
        foreach ($pets as $pet){
            if($pet->sales_volume < 2000){
                $pet->sales_volume = rand(2100, 8999);
            }else{
                $pet->sales_volume += rand(1, 5);
            }
            $pet->save();
        }
    }
}
