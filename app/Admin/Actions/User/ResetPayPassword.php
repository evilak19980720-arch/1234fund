<?php

namespace App\Admin\Actions\User;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPayPassword extends RowAction
{
    public $name = '重置支付密码';

    public function handle(Model $model, Request $request)
    {
        $payment_password = Hash::make($request->get('payment_password'));
        $model->payment_password = $payment_password;
        $model->save();
        return $this->response()->success('重置支付密码成功!')->refresh();
    }

    public function form()
    {
        $this->password('payment_password', '支付密码')->required();
    }
}
