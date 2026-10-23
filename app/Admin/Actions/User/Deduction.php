<?php

namespace App\Admin\Actions\User;

use App\Models\Account;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Deduction extends RowAction
{
    public $name = '扣款';

    public function handle(Model $model,Request $request)
    {
        $money = $request->money * 100;
        if ($money > $model->where('id', $model->id)->value('balance'))
        {
            return $this->response()->error('用户余额不足')->refresh();
        }
        DB::transaction(function () use ($model,$money) {
            $model->where('id', $model->id)->decrement('balance', $money);
            Account::create([
                'type' => Account::SYSTEM_DEDUCTION,
                'to' => 0,
                'from' => $model->id,
                'amount' => -$money,
                'level' => 0
            ]);
        });

        return $this->response()->success('扣款成功' )->refresh();
    }

    public function form()
    {
        $this->text('money', '扣款金额')->rules('required|numeric|min:1');
    }
}