<?php

namespace App\Jobs;

use App\Models\Tran;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class GoogleTrans implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $type;
    protected $key;
    protected $value;
    protected $desc;

    public function __construct($type, $key, $value, $desc)
    {
        $this->type = $type;
        $this->key = $key;
        $this->value = $value;
        $this->desc = $desc;
    }

    public function handle()
    {

        app(Tran::class)->tran($this->type , $this->key, $this->value, $this->desc);

    }

}
