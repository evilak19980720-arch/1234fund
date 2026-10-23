<?php

namespace App\Console\Commands;

use App\Models\Issue;
use App\Models\User;
use App\Services\FundingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanCommand extends Command
{
    protected $signature = 'issue:clean';

    protected $description = '扫货';

    private $second = 28;

    private $issues = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        DB::transaction(function () {
            $this->currentIssue()->allIn();
        });
    }

    private function allIn()
    {
        if (!$this->issues) {
            return false;
        }

        foreach ($this->issues as $issue) {
            $this->buy($issue);
        }
    }

    private function buy(Issue $issue)
    {
        $robots = $this->getRobots();
        $index = count($robots) - 1;
        $stock = $issue->portion - $issue->bought;

        if ($stock > 0) {
            $service = new FundingService($issue);

            foreach ($robots as $key => $robot) {
                if ($key == $index && $stock > 0) {
                    $service->robot()->buy($robot->id, $stock);
                } else {
                    $n = mt_rand(0, $stock);
                    if ($n > 0) {
                        $service->robot()->buy($robot->id,$n);
                    }
                    $stock = $stock - $n;
                }
            }
        }
    }

    private function getRobots()
    {
        return User::where('hierarchy_id', 4)->inRandomOrder()->take(mt_rand(1,4))->get();
    }

    private function currentIssue()
    {
        $now = Carbon::now();
        $issues = Issue::where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where('draw', 0)
            ->lockForUpdate()
            ->get();

        if ($issues) {
            foreach ($issues as $issue) {
                if ($now->diffInSeconds($issue->end_time) < $this->second) {
                    array_push($this->issues, $issue);
                }
            }
        }

        return $this;
    }
}
