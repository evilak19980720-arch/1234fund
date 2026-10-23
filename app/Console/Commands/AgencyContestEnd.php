<?php

namespace App\Console\Commands;

use App\Models\AgencyContest;
use App\Models\AgencyContestUser;
use App\Models\Configuration;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AgencyContestEnd extends Command
{

    protected $signature = 'AgencyContestEnd';

    protected $description = '代理争霸结束';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $AgencyContestRewardModel = Configuration::getItem('AgencyContestRewardModel');
        $agency_contests = AgencyContest::query()->where('end_time','<',now())->where('status','=',1)->get();
        if($agency_contests->isNotEmpty()){
            foreach ($agency_contests as $agency_contest){
                $agency_contest->status = 2;
                $agency_contest->save();
                if($AgencyContestRewardModel == 3){
                    $win_user = AgencyContestUser::query()
                        ->where('agency_contest_id','=',$agency_contest->id)
                        ->orderByDesc('active_member_count')
                        ->orderByDesc('accumulated_commission')
                        ->orderBy('created_at')
                        ->first();
                    $win_user->isfirst = 1;
                    $win_user->save();
                }
                if($AgencyContestRewardModel == 1){
                    $win_users = AgencyContestUser::query()
                        ->where('agency_contest_id','=',$agency_contest->id)
                        ->orderByDesc('active_member_count')
                        ->orderByDesc('accumulated_commission')
                        ->orderBy('created_at')
                        ->limit(3)
                        ->get();
                    foreach ($win_users as $key=>$win_user){
                        $win_user->isfirst = 3 - $key;
                        $win_user->save();
                    }
                }
            }
        }




    }
}
