<?php

namespace App\Admin\Actions\Order;

use App\Jobs\PayOrder;
use App\Models\Configuration;
use App\Models\Order;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * 订单重试
 * @package App\Admin\Actions\Order
 */
class Retry extends RowAction
{
    public $name = '重试';

    public function handle(Model $model)
    {
        if ($model->closed == 1) {
            return $this->response()->error('订单已关闭')->refresh();
        }

        if ($model->state != Order::FAILED) {
            return $this->response()->error('订单状态不允许重试')->refresh();
        }

        $switch = Configuration::getItem('third_payment_switch');
        if (!$switch) {
            return $this->response()->error('第三方出款未开启')->refresh();
        }

        DB::transaction(function () use ($model) {

            // 记录旧订单
            $model->logs()->create([
                'admin_id' => Admin::user()->id,
                'order_id' => $model->id,
                'no' => $model->no,
                'payment_no' => $model->payment_no,
                'error' => $model->operation,
                'paid_at' => $model->paid_at
            ]);

            // 变更当前订单
            $model->no = only_code();
            $model->payment_no = '';
            $model->paid_at = '';
            $model->closed = 0;
            $model->state = 0;
            $model->remark = '';
            $model->operation = '';
            $model->save();

            // 发起新订单
            PayOrder::dispatch($model)->onQueue(env('REDIS_QUEUE', 'default'));
        });

        return $this->response()->success('订单已发起重试.')->refresh();
    }

}
