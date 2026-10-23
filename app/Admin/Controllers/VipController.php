<?php

namespace App\Admin\Controllers;

use App\Models\Vip;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class VipController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Vip设置';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Vip());

        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        // 禁用筛选
        $grid->disableFilter();

        $grid->disableCreateButton();

        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });

        $grid->column('id', __('Id'));
        $grid->column('level', '等级');
        $grid->column('name', 'vip名称');

        //$grid->column('cash_out', '总提现次数');
        $grid->column('subordinate_buy', '升级需要的下级购买数');
        $grid->column('daily_cash_out', '每日提现次数');
        $grid->column('daily_withdrawal_amount', '每日提现金额上限')->display(function ($value) {
            return $value / 100;
        });
        $grid->column('single_withdrawal_amount', '单次提现金额上限')->display(function ($value) {
            return $value / 100;
        });
        $grid->column('withdrawal_fee', '提现手续费')->display(function ($withdrawal_fee) {
            return $withdrawal_fee.' U';
        });
        $grid->column('send_amount', '赠送金额')->display(function ($value) {
            return $value / 100;
        });
        $grid->column('financing_max_amount', '融资最大金额')->display(function ($value) {
            return $value / 100;
        });
        $grid->column('discount', '购买折扣');


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
        $show = new Show(Vip::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('level', __('Level'));
        $show->field('name', __('Name'));
        $show->field('state', __('State'));
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
        $form = new Form(new Vip());

        $form->tools(function (Form\Tools $tools) {
            // 去掉`查看`按钮
            $tools->disableView();
            // 去掉`删除`按钮
            $tools->disableDelete();
        });
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->editing(function (Form $form) {
            $form->model()->daily_withdrawal_amount = $form->model()->daily_withdrawal_amount / 100;
            $form->model()->single_withdrawal_amount = $form->model()->single_withdrawal_amount / 100;
            $form->model()->send_amount = $form->model()->send_amount / 100;
            $form->model()->financing_max_amount = $form->model()->financing_max_amount / 100;
        });

        $form->saving(function (Form $form) {
            $form->daily_withdrawal_amount = $form->daily_withdrawal_amount * 100;
            $form->single_withdrawal_amount = $form->single_withdrawal_amount * 100;
            $form->send_amount = $form->send_amount * 100;
            $form->financing_max_amount = $form->financing_max_amount * 100;
        });

        if ($form->isEditing()) {
            $form->text('level', '等级')->disable();
        } else {
            $form->number('level', '等级')->min(0)->max(9)->required();
        }
        $form->text('name', '名称')->required();
        $form->number('subordinate_buy', '升级需要的下级购买数')->required()->min(0);
        $form->number('daily_cash_out', '每日提现次数')->required()->min(0);
        $form->currency('daily_withdrawal_amount','每日提现金额上限')->symbol('$')->required();
        $form->currency('single_withdrawal_amount','单次提现金额上限')->symbol('$')->required();
        $form->text('withdrawal_fee', '提现手续费(U)')->required();
        $form->number('send_amount', '赠送金额(U)')->required()->min(0);
        $form->number('financing_max_amount', '融资最大金额(U)')->required();
        $form->text('discount', '购买折扣')->default(1)->required()->help(' 9折填0.9');

        return $form;
    }
}
