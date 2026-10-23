<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 任务过期
 * @package App\Console\Commands
 */
class TaskOverdue extends Command
{
    protected $signature = 'task:overdue';

    protected $description = '任务过期';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        return 0;
    }
}
