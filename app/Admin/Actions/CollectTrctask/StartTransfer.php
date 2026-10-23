<?php

namespace App\Admin\Actions\CollectTrctask;

use App\Jobs\CollectTrc;
use App\Models\CollectTrctaskLog;
use App\Models\Configuration;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * 订单重试
 * @package App\Admin\Actions\Order
 */
class StartTransfer extends RowAction
{
    public $name = '开始转移';

    public function handle(Model $model)
    {
        //$model->id
        if ($model->state != 0) {
            return $this->response()->error('任务状态不允许开始转移')->refresh();
        }

        DB::transaction(function () use ($model) {
            //修改库数据,每一条写队列
            $model->state = 5;
            $model->save();
            CollectTrctaskLog::query()->where('collect_trctask_id','=',$model->id)->update([
                'state' => 5
            ]);
            CollectTrctaskLog::query()->where('collect_trctask_id','=',$model->id)->chunkById(100, function ($logs){
                foreach ($logs as $log){
                    CollectTrc::dispatch('transfer',['collect_trctask_log_id'=>$log->id])->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                }
            });


        });

        return $this->response()->success('任务已开始转移.')->refresh();
    }



}
