<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class PhoneCommand extends Command
{
    protected $signature = 'record:phone';

    protected $description = 'cell-phone';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $yesterday = carbon::yesterday();
        $ymd = $yesterday->format('ymd');

        $i = 0;
        $file= sprintf('phones/%d%d%d.txt',
            $yesterday->format('Y'),
            $yesterday->format('m'),
            $yesterday->format('d'));
        Storage::disk('local')->put($file, '');
        $file2 = storage_path('app/'.$file);
        do {
            $options['limit']['offset'] = $i;
            $i += 50000;
            $options['limit']['count'] = 50000;
            $rows = redis::zrangebyscore('addressbook', $ymd, $ymd,$options);
            if ($rows) {
                $str = implode(',',$rows);
                if(file_exists($file2)){
                    file_put_contents($file2, $str, FILE_APPEND);
                }
            }
        } while (count($rows) != 0);



    }
}
