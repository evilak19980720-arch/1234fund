<?php

namespace App\Admin\Actions\User;

use App\Models\Account;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Recharge extends RowAction
{
    public $name = '充值';
    private $expire = 10;
    public function handle(Model $model, Request $request)
    {
        // $model ...
        $money = $request->money * 100;
        Cache::lock(sprintf('audit:%d', $model->id), $this->expire)->get(function () use ($model,$money) {
            DB::transaction(function () use ($model,$money) {
                //$model->balance += $money * 100;
                //$model->save();
                $model->where('id', $model->id)->increment('balance', $money);
                Account::create([
                    'type' => Account::SYSTEM_RECHARGE,
                    'to' => $model->id,
                    'from' => 0,
                    'amount' => $money,
                    'level' => 0
                ]);
            });
        });

        return $this->response()->success('充值成功' )->refresh();
    }

    public function form()
    {
        $this->text('money', '充值金额')->rules('required|numeric|min:1');
    }
}
