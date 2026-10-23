<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use App\Models\Atlas;
use App\Models\Crowd;
use App\Models\Fund;
use App\Models\Notice;
use App\Models\Pet;
use App\Models\RedLadder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AddAgentData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AddAgentData';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '为代理增加 宠物、图集、众筹-彩种、阶梯红包数据，公告，数据拷贝admin_user_id = $data_source_admin_user_id 的数据(系统代理)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //1宠物 2图集   众筹-彩种   阶梯红包
        $data_source_admin_user_id = 15;
        $admin_user_id = $this->ask('请输入代理商ID');
        $admin_user = AdminUser::query()->find($admin_user_id);
        if (!$admin_user)
        {
            $this->error('未查询到代理商');
        }
        if (Pet::query()->where('admin_user_id','=',$admin_user_id)->count())
        {
            $this->error('代理商已拥有宠物数据');
        }
        $pets = Pet::query()->where('admin_user_id','=',$data_source_admin_user_id)->get()->toArray();
        if (!count($pets))
        {
            $this->error('系统代理缺少宠物数据');
        }

        $pet_id_arr = [];//宠物ID对应关系
        //拷贝宠物数据
        foreach ($pets as $pet)
        {
            $pet_id = $pet['id'];
            unset($pet['id']);
            $pet['admin_user_id'] = $admin_user_id;
            $pet['created_at'] = Carbon::now();
            $pet['updated_at'] = Carbon::now();
            $id = Pet::query()->insertGetId($pet);
            $pet_id_arr[$pet_id] = $id;
        }
        //拷贝宠物图集
        $full_atlas = Atlas::query()->whereIn('pet_id',array_keys($pet_id_arr))->get()->toArray();
        $insert_atlas = [];
        foreach ($full_atlas as $item_atlas)
        {
            $atlas['title'] = $item_atlas['title'];
            $atlas['pet_id'] = $pet_id_arr[$item_atlas['pet_id']];
            $atlas['img'] = json_encode($item_atlas['img']);
            $atlas['created_at'] = Carbon::now();
            $atlas['updated_at'] = Carbon::now();
            unset($atlas['id']);
            $insert_atlas[] = $atlas;
        }
        Atlas::query()->insert($insert_atlas);
        //拷贝  众筹-彩种
        $insert_fund = [];
        $fund = Fund::query()->where('admin_user_id','=',$data_source_admin_user_id)->whereIn('pet_id',array_keys($pet_id_arr))->get()->toArray();
        foreach ($fund as $item) {
            $item['pet_id'] = $pet_id_arr[$item['pet_id']];
            $item['admin_user_id'] = $admin_user_id;
            $item['created_at'] = Carbon::now();
            $item['updated_at'] = Carbon::now();
            unset($item['id']);
            $insert_fund[] = $item;
        }
        Fund::query()->insert($insert_fund);

        //拷贝  阶梯红包
        $full_red_ladders = RedLadder::query()->where('admin_user_id','=',$data_source_admin_user_id)->get()->toArray();
        $insert_red_ladders = [];
        foreach ($full_red_ladders as $item_red_ladders)
        {
            $item_red_ladders['admin_user_id'] = $admin_user_id;
            $item_red_ladders['pet_id'] = $pet_id_arr[$item_red_ladders['pet_id']];
            $item_red_ladders['subordinate_pet_id'] = $pet_id_arr[$item_red_ladders['subordinate_pet_id']];
            $item_red_ladders['created_at'] = Carbon::now();
            $item_red_ladders['updated_at'] = Carbon::now();
            unset($item_red_ladders['id']);
            $insert_red_ladders[] = $item_red_ladders;
        }
        RedLadder::query()->insert($insert_red_ladders);

        //拷贝  公告
        $notice = Notice::query()->where('admin_user_id','=',$data_source_admin_user_id)->select('admin_user_id','title','content','top','eject')->get();
        $notice->map(function($item) use ($admin_user_id){
            $item->admin_user_id = $admin_user_id;
            $item->created_at = Carbon::now();
            $item->updated_at = Carbon::now();
        });
        Notice::query()->insert($notice->toArray());

        $this->info('数据拷贝成功');
    }
}
