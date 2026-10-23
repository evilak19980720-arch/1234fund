<?php

namespace App\Console\Commands;

use App\Models\PetOrder;
use Illuminate\Console\Command;

class RecyclePetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recycle:pet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动回收成熟宠物';

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
     * @return void
     */
    public function handle()
    {
        if(config('app.revenue_model') != 'all'){
            return;
        }

        //查询成熟宠物
        PetOrder::query()->with(['pet', 'user'])
            ->where('type', '=', 1)
            ->where('state', '=', 0)
            ->whereRaw('TIMESTAMPDIFF(HOUR,`start_at`,now()) >= (`day` * 24) + 24')
            ->chunkById(100, function ($orders) {
                //判断是否开启必须持有指定宠物才能卖出
                foreach ($orders as $order) {
/*                    if ($order->pet->next_pet_id) {
                        $next_pet_order = PetOrder::query()->where('id', '>', $order->id)
                            ->where('user_id', '=', $order->user->id)
                            ->where('state', '=', 0)
                            ->where('pet_id', '=', $order->pet->next_pet_id)
                            ->first();
                        if (!$next_pet_order) {
                            //未拥有限制宠物 不予自动回收
                            continue;
                        }
                    }*/
                    //卖出宠物
                    app(PetOrder::class)->sell($order->user, $order, $order->pet);
                }

            });
    }
}
