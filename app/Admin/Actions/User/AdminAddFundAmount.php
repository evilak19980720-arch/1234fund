<?php

namespace App\Admin\Actions\User;

use Encore\Admin\Actions\RowAction;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{User, FundAmountAccount};

class AdminAddFundAmount extends RowAction
{
    public $name = '增加众筹余额';

    function handle(User $user, Request $request)
    {
        $amount = $request->amount * 100;
        try {
            DB::beginTransaction();
            $user->increment('fund_amount', $amount);
            $fundAmountAccount = new FundAmountAccount;
            $fundAmountAccount->to = $user->id;
            $fundAmountAccount->type = FundAmountAccount::TYPE_ADMIN_INCREASE;
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
        $this->text('amount', '增加金额')->rules('required|numeric|min:1');
    }
}
