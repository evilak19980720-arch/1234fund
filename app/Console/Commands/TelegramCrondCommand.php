<?php

namespace App\Console\Commands;

use App\Models\Configuration;
use App\Models\TelegramCrond;
use App\Models\TelegramCrondJihua;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class TelegramCrondCommand extends Command
{
    protected $signature = 'telegram:crond';

    protected $description = '飞机定时群发';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //普通消息
        $tasklist = TelegramCrond::query()
            //->whereHas('group')
            //->with(['group'=>function($q){
            //    $q->with(['bot','template']);
            //}])

            ->whereTime('send_time','=',Carbon::now()->format('H:i:00'))
            ->where(function ($query){
                $query->whereDate('last_send_time','<>',Carbon::now())->orWhereNull('last_send_time');
            })
            ->orderBy('id')
            ->get();
        foreach ($tasklist as $task){
            $task->sendMsg();
        }



    }




}
