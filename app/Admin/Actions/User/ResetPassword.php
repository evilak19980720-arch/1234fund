<?php

namespace App\Admin\Actions\User;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPassword extends RowAction
{
    public $name = '重置密码';

    public function handle(Model $model,Request $request)
    {
        $password = Hash::make($request->get('password'));
        $model->password = $password;
        $model->save();
        return $this->response()->success('重置密码成功!')->refresh();
    }

    public function form()
    {
        $this->password('password','密码')->required();
    }

}
