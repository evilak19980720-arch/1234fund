<?php

namespace App\Console\Commands;

use App\Models\AgencyContest;
use App\Models\AgencyContestUser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AgencyContestFakeUser extends Command
{

    protected $signature = 'AgencyContestFakeUser';

    protected $description = '代理争霸假会员自动加数量';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        /*
         * 新增用户基数
            比如我设置的2  那么这个虚拟用户每2个小时新增2x(随机数0-9)
            佣金基数5  就每2个小时新增佣金5x(0-9)
         * */
        $agency_users = AgencyContestUser::query()->with('agency_contest')
            ->whereHas('agency_contest',function($sql){
                $sql->where('start_time','<',now())->where('end_time','>',now())->where('status','=',1);
            })
            ->get();
        foreach ($agency_users as $agency){
            $active_member_count = $agency->dayadd_active_member_count * rand(0,5);
            $accumulated_commission = $agency->dayadd_accumulated_commission * rand(0,5) * 100;
            $agency->increment('active_member_count',$active_member_count);
            $agency->increment('accumulated_commission',$accumulated_commission);
        }
    }
}
