<?php

namespace App\Admin\Actions\RechargeAddress;

use App\Jobs\CheckBscBalance;
use App\Jobs\CheckTrcBalance;
use App\Models\UserRechargeAddress;
use App\Services\TronService;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Facades\Admin;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BalanceAll extends BatchAction
{
    public $name = '更新全部余额';

    public function handle(Collection $collection)
    {
        $all_address = UserRechargeAddress::all();
        foreach ($all_address as $model) {
            if($model->block == 'TRC'){
                CheckTrcBalance::dispatch($model->address)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
            }elseif($model->block == 'BSC'){
                CheckBscBalance::dispatch($model->address)->onQueue(env('REDIS_QUEUE_LOW', 'default'));
            }

        }

        return $this->response()->success('Success message...')->refresh();
    }


}
