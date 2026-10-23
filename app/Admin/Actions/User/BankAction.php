<?php

namespace App\Admin\Actions\User;

use App\Models\Bank;
use App\Models\Card;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Encore\Admin\Actions\RowAction;

class BankAction extends RowAction
{
    public $name = '银行卡';

    public function handle(Model $model,Request $request)
    {
        $card = Card::query()->where('user_id','=',$model->id)->first();
        if(!$card)
        {
            return $this->response()->success('未进行任何修改')->refresh();
        }
        $card->update($request->only('number'));
        return $this->response()->success('修改成功!')->refresh();
    }

    public function form(Model $model)
    {
        $bank_card = Card::query()->where('user_id','=',$model->id)->first();
        if (!$bank_card)
        {
            $this->text('bank','银行卡')->default('用户未填写')->readonly();
            return;
        }
        //$this->select('bank_id','银行')->options(Bank::query()->pluck('name','id'))->default($bank_card->bank_id);
        $this->text('number','卡号')->default($bank_card->number)->rules('required');
        //$this->text('ifsc','ifsc')->default($bank_card->ifsc)->rules('required');
        //$this->text('username','真实姓名')->default($bank_card->username)->rules('required');
        //$this->text('phone','手机号')->default($bank_card->phone)->rules('required');
        //$this->text('email','邮箱')->default($bank_card->email)->rules('required|email');

    }

}
