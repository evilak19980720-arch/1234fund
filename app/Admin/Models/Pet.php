<?php

namespace App\Admin\Models;

use App\Models\Account;
use App\Models\PetOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Pet extends Model
{
    public $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    //处理预约订单转购买订单跟分成
    public function PreFishAndCommission(\App\Models\Pet $pet)
    {
        //查询预约订单
        $pet_orders = PetOrder::where(['pet_id' => $pet->id, 'type' => 2, 'cancelled' => 0])->orderBy('id')->get();
        $count = $pet->stock_quantity;
        if ($pet_orders->isEmpty() || $count <= 0) {
            return;
        }
        DB::transaction(function () use ($pet_orders, $pet, $count) {
            foreach ($pet_orders as $item) {
                if ($count < $item->number) {
                    continue;
                }
                PetOrder::query()->where('id', $item->id)->update(['type' => 1, 'start_at' => date('Y-m-d')]);
                $pet->decrement('stock_quantity', $item->number);
                $user = $item->user;
                //计算分成
                $this->account->buyCommission($user, $item->amount);
                $count -= $item->amount;
                if ($count <= 0) {
                    break;
                }
            }
        },2);
    }
}
