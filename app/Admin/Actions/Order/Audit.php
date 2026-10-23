<?php

namespace App\Admin\Actions\Order;

use App\Jobs\PayOrder;
use App\Models\Account;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Facades\Admin;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Audit extends BatchAction
{
    public $name = '批量审核';
    private $expire = 30;
    private $state;
    private $remark;
    private $payment;
    private $usdt_private_key;

    public function handle(Collection $collection)
    {
        $this->state = request()->get('states');
        $this->remark = request()->get('remark');
        $this->payment = Payment::where('id', '=', request()->get('payment_id'))->first();
        $this->usdt_private_key = request()->get('usdt_private_key');
        foreach ($collection as $model) {
            Cache::lock(sprintf('audit:%d', $model->id), $this->expire)->get(function () use ($model) {
                DB::transaction(function () use ($model) {
                    $order = Order::where('id', '=', $model->id)->lockForUpdate()->first();
                    if ($order->state != Order::REVIEW) {
                        return false;
                    }

                    $this->record($order);

                    switch ($this->state) {
                        case Order::PENDING:
                            $this->approve($order);
                            break;
                        case Order::REFUSE:
                            $this->refuse($order);
                            break;
                        case Order::CANCEL:
                            $this->cancel($order);
                            break;
                        case Order::SUCCESS:
                            (new Order())->withdrawComplete([
                                'state' => Order::SUCCESS,
                                'orderId' => $order->no,
                                'paidAt' => Carbon::now()->timestamp
                            ],['mag'=>'后台操做']);
                            break;
                    }
                });
            });
        }

        return $this->response()->success('Success message...')->refresh();
    }

    public function form()
    {
        $this->select('payment_id', '支付方式')->options(Payment::query()
            ->open()
            ->withdrawal(Payment::WITHDRAWAL_OPEN)
            ->where('admin_user_id', '=', Admin::user()->isRole('agents') ? Admin::user()->id : 0)
            ->pluck('title', 'id')->toArray())->required();
        $this->radio('states', '状态')->options([
            Order::PENDING => '通过',
            Order::REFUSE => '拒绝',
            Order::CANCEL => '取消',
            Order::SUCCESS => '成功',
        ])->required();
        $this->text('usdt_private_key', '私钥');
        $this->text('remark', '备注');
    }

    private function record(Order $order)
    {
        $order->logs()->create([
            'admin_id' => Admin::user()->id,
            'order_id' => $order->id,
            'no' => $order->no,
            'payment_no' => $order->payment_no,
            'remark' => $order->remark,
            'error' => $order->operation,
            'paid_at' => $order->paid_at
        ]);
    }

    private function approve(Order $order)
    {

        if($this->payment->currery == $order->currery){
            if ($order->payment_no) {
                $order->no = only_code();
                $order->payment_no = '';
                $order->paid_at = '';
                $order->closed = 0;
                $order->operation = '';
            }
            if (!$order->platform) {
                $order->platform = $this->payment->server_name;
                $order->payment_id = $this->payment->id;
            }
            $order->remark = $this->remark;
            $order->state = $this->state;
            $order->save();
            PayOrder::dispatch($order,['usdt_private_key'=>$this->usdt_private_key])->onQueue(env('REDIS_QUEUE', 'default'))->delay(now()->addSeconds(5));
        }
    }

    private function refuse(Order $order)
    {
        User::where('id', '=', $order->user_id)->decrement('frozen', $order->amount);
        if (!$order->platform) {
            $order->platform = $this->payment->server_name;
            $order->payment_id = $this->payment->id;
        }
        $order->state = $this->state;
        $order->remark = $this->remark;
        $order->closed = 1;
        $order->save();
    }

    private function cancel(Order $order)
    {
        User::where('id', '=', $order->user_id)->decrement('frozen', $order->amount);
        User::where('id', '=', $order->user_id)->increment('balance', $order->amount);
        Account::create([
            'order_id' => $order->id,
            'type' => Account::WITHDRAWAL_FAIL,
            'to' => $order->user_id,
            'form' => 0,
            'amount' => $order->amount,
            'level' => 0
        ]);
        User::where('id', '=', $order->user_id)->decrement('cash_out_times', 1);
        if (!$order->platform) {
            $order->platform = $this->payment->server_name;
            $order->payment_id = $this->payment->id;
        }
        $order->state = $this->state;
        $order->remark = $this->remark;
        $order->closed = 1;
        $order->save();
    }
}
