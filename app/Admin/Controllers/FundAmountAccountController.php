<?php

namespace App\Admin\Controllers;

use App\Models\FundAmountAccount;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FundAmountAccountController extends AdminController
{
    protected $title = '用户众筹余额流水';

    protected function grid()
    {
        $grid = new Grid(new FundAmountAccount());
        $grid->model()->with('user')
            ->whereHas('user',function($query){
                $query->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0);
            })
            ->orderBy('created_at', 'desc')->paginate();

        $grid->column('user.id', '用户id');
        $grid->column('user.phone', '用户手机');
        $grid->column('type', '类型')->display(function ($type) {
            return FundAmountAccount::TYPE_TEXT_ARR[$type];
        });
        $grid->column('amount', '金额')->display(function ($amount) {
            $amount = bcdiv((string)$amount, 100, 2);
            if ($this->type != FundAmountAccount::TYPE_ADMIN_INCREASE) {
                $amount = -$amount;
            }
            return $amount;
        });
        $grid->column('created_at', '时间');
        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableExport();
        $grid->disableRowSelector();

        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            $filter->equal('to', '用户id');
            $filter->equal('type', '类型')->select(FundAmountAccount::TYPE_TEXT_ARR);
            $filter->like('user.phone', '用户手机');
        });

        return $grid;
    }
}
