<?php

namespace App\Admin\Actions\User;

use Encore\Admin\Actions\RowAction;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{User, FundAmountAccount};

class AdminSubFundAmount extends RowAction
{
    public $name = '扣除众筹余额';

    function handle(User $user, Request $request)
    {
        $amount = $request->amount * 100;
        if ($amount > $user->fund_amount) {
            return $this->response()->error('扣除金额超过用户金额');
        }
        try {
            DB::beginTransaction();
            $user->decrement('fund_amount', $amount);
            $fundAmountAccount = new FundAmountAccount;
            $fundAmountAccount->to = $user->id;
            $fundAmountAccount->type = FundAmountAccount::TYPE_ADMIN_DECREASE;
            $fundAmountAccount->amount = $amount;
            $fundAmountAccount->save();
            DB::commit();
            return $this->response()->success('成功')->refresh();
        } catch (\Exception $e) {
            DB::rollBack();
        }
        return $this->response()->error('失败');
    }

    function form()
    {
        $this->text('amount', '扣除金额')->rules('required|numeric|min:1');
    }
}
