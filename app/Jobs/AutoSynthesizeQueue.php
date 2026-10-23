<?php

namespace App\Jobs;

use App\Models\Pet;
use App\Models\PetOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AutoSynthesizeQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pet;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($pet)
    {
        $this->pet = $pet;
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     */
    public function handle()
    {
        $pet = $this->pet;

        DB::transaction(function () use ($pet) {
            $pet = Pet::where('id', $pet->id)->first();//withTrashed()
            if ($pet->waiting_quantity > 1) {
                //生成下一级宠物
                $pet_up = Pet::where('level', $pet->level + 1)->first();
                //如果下一级宠物存在 则执行下列操作
                if ($pet_up) {
                    //增加下一级的库存
                    $pet_up->increment('stock_quantity', intval($pet->waiting_quantity / 2));
                    //增加下一级自动合成数量
                    $pet_up->increment('automatic_quantity', intval($pet->waiting_quantity / 2));
                    //更新等待合成的数量
                    $pet->decrement('waiting_quantity', intval($pet->waiting_quantity / 2) * 2);

                    //给预约的用户划拨宠物 减去相应的库存
                    $pet_orders = PetOrder::where(['pet_id' => $pet_up->id, 'type' => 2])->orderBy('created_at', 'asc')->get();
                    $pet_up_count = $pet_up->stock_quantity;
                    //如果存在预约订单 并且下一级库存大于0 则开始执行下列操作
                    if ($pet_orders->isNotEmpty() && $pet_up_count > 0) {
                        foreach ($pet_orders as $item) {
                            $pet_up_count -= $item->number;
                            if ($pet_up_count < 0) break;
                            //预约修改为购买,增加收益周期开始日期
                            PetOrder::where('id', $item->id)->update(['type' => 1, 'start_at' => date('Y-m-d')]);
                        }
                        //库存变化值
                        $change_quantity = $pet_up->stock_quantity - $pet_up_count;
                        //减库存
                        $pet_up->decrement('stock_quantity', $change_quantity);
                    }
                }
            }
            return 1;
        });

    }
}
