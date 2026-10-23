<?php

namespace App\Admin\Controllers;

use App\Admin\Models\Today;
use App\Admin\Render\ShowOrder;
use App\Models\Order;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Godruoyi\Snowflake\Snowflake;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Carbon;

class TodayController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '今日统计';
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Today());
        $grid->disableExport();
        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableActions();
        $grid->disablePagination();
        //$grid->disableFilter();

        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->between('this_date', '日期')->date();
            $filter->like('parent_phone', '收款人');
            $filter->expand();
        });

        $grid->column('this_date', '日期');
        $grid->column('reg', '今日注册');
        $grid->column('recharge', '今日充值')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('recharge_user', '今日充值人数');
        $grid->column('again', '今日复充');
        $grid->column('withdrawal', '今日提现')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('wait_petorder', '预计到账')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('withdrawal_pending', '未提现资金')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('withdrawal_user', '今日提现人数');

        $grid->column('withdrawal_first_amount', '首次提款')->display(function ($var) {
            return $var / 100 . "(".$this->withdrawal_first_count.")";
        });

        /*
        if(Admin::user()->isRole('administrator')){
            $grid->column('install', '今日安装');
        }
        $grid->column('fund', '今日投注')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('win_fund', '今日中奖')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('fund_user', '今日投注人数');
        $grid->column('win_fund_user', '今日中奖人数');

        $grid->column('fund_count', '今日投注次数');
        $grid->column('win_fund_count', '今日中奖次数');
        */
        return $grid;
    }




    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Order::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('platform', __('Platform'));
        $show->field('amount', __('Amount'));
        $show->field('no', __('No'));
        $show->field('payment_no', __('Payment no'));
        $show->field('paid_at', __('Paid at'));
        $show->field('closed', __('Closed'));
        $show->field('type', __('Type'));
        $show->field('state', __('State'));
        $show->field('remark', __('Remark'));
        $show->field('operation', __('Operation'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Order());

        $form->number('user_id', __('User id'));
        $form->text('platform', __('Platform'));
        $form->number('amount', __('Amount'));
        $form->text('no', __('No'));
        $form->text('payment_no', __('Payment no'));
        $form->text('paid_at', __('Paid at'));
        $form->switch('closed', __('Closed'));
        $form->switch('type', __('Type'));
        $form->switch('state', __('State'));
        $form->text('remark', __('Remark'));
        $form->textarea('operation', __('Operation'));

        return $form;
    }
}
