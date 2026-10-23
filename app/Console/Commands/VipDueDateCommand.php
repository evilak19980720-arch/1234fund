<?php


namespace App\Console\Commands;


use App\Jobs\PayOrder;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class VipDueDateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vipDueDate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'vip到期时间';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        User::where('vip','>',0)->where('vip_due_date','<',Carbon::now())->update(['vip'=>0,'vip_due_date'=>'']);

        //到期自动销毁
        User::where('destroy','>',0)->get(['id','destroy','updated_at'])->map(function ($item){
            $isDelete = false;
            //1个月
            if ($item->destroy==1 && (time()-$item->updated_at)>=3600*24*30){
                $isDelete = true;
            }
            //2个月
            if ($item->destroy==2 && (time()-$item->updated_at)>=3600*24*30*2){
                $isDelete = true;
            }
            //3个月
            if ($item->destroy==3 && (time()-$item->updated_at)>=3600*24*30*3){
                $isDelete = true;
            }
            //6个月
            if ($item->destroy==4 && (time()-$item->updated_at)>=3600*24*30*6){
                $isDelete = true;
            }
            //12个月
            if ($item->destroy==5 && (time()-$item->updated_at)>=3600*24*30*12){
                $isDelete = true;
            }
            $isDelete && User::where('id',$item->id)->delete();

        });

        //更新用户updated_at
        $list = User::where('is_robot',0)->get();
        foreach ($list as $value){
            if (Cache::has('user_updated_at_'.$value->id)){
                User::where('id',$value->id)->update(['updated_at'=>Carbon::now()]);
            }
        }
    }


}