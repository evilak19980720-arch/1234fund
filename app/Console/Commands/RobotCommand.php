<?php

namespace App\Console\Commands;

use App\Admin\Actions\Order\Log;
use App\Models\Issue;
use App\Models\User;
use App\Services\FundingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RobotCommand extends Command
{
    protected $signature = 'issue:robot';

    protected $description = '机器人购买';

    public $robot;
    public $robots;

    private $lower = 10;

    private $upper = 20;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        sleep(mt_rand(0,30));
        DB::transaction(function () {
            $issues = $this->currentIssue();
            if ($issues) {
                foreach ($issues as $issue) {
                    $this->getRobot()->buy($issue);
                }
            }
        });
    }

    private function buy(Issue $issue)
    {

        $m = Carbon::parse($issue->start_time)->diffInMinutes(Carbon::parse($issue->end_time), true);
        $nowm = Carbon::now()->format('i');

        if($m > 5 && $nowm % round($m/5)  != 0){
            return false;
        }

        $stock = $issue->portion - $issue->bought;
        if ($stock <= 0) {
            return false;
        }

        $proportion = mt_rand($this->lower, $this->upper) / 100;
        //$num = round($stock * $proportion);
        $num = round($issue->portion * $proportion);
        $num = $num < 1 ? 1 : $num;

        //\Illuminate\Support\Facades\Log::error("$issue->portion | $proportion | $num");

        $service = new FundingService($issue);

        //$service->robot()->buy($this->robot->id, $num);

        foreach ($this->robots as $key => $robot) {
            $n = $this->getnum($num);
            if ($n > 0) {
                $service->robot()->buy($robot->id,$n);
            }
            $num = $num - $n;
        }

    }


    private function getnum($num){
        $n = mt_rand(0, $num);
        return $n;
    }

    private function currentIssue()
    {
        $now = Carbon::now();
        return Issue::where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where('draw', 0)
            ->sharedLock()
            ->get();
    }

    private function getRobot()
    {
        $users = User::where('hierarchy_id', 4)->inRandomOrder()->take(mt_rand(1,20))->get();
        if (!$users) {
            $this->createRobot();
            $users = User::where('hierarchy_id', 4)->inRandomOrder()->take(mt_rand(1,20))->get();
        }
        $this->robot = $users[0];
        $this->robots = $users;
        return $this;
    }

    private function createRobot()
    {
        for ($i = 1; $i <= 100; $i++) {
            $phone = mt_rand(601, 998) . '0000' . mt_rand(101, 999);
            $users[] = [
                'phone' => $phone,
                'name' => $phone,
                'password' => Hash::make('sr4S6s487'),
                'avatar' => mt_rand(1, 12),
                'hierarchy_id' => 4,
                'buy_rebate' => 0,
                'hidephone' => 1,
                'created_at' => Carbon::create(2021, 1, 1, 00, 00, 00)->toDateTimeString(),
                'updated_at' => Carbon::create(2021, 1, 1, 00, 00, 00)->toDateTimeString(),
            ];
        }
        DB::table('users')->insert($users);
    }
}
