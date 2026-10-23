<?php

namespace App\Admin\Controllers;

use App\Models\OrderLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class OrderLogController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'OrderLog';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new OrderLog());
        $grid->column('id', __('Id'));
        $grid->column('order_id', __('Order id'));
        $grid->column('admin_id', __('Admin id'));
        $grid->column('no', __('No'));
        $grid->column('payment_no', __('Payment no'));
        $grid->column('remark', __('Remark'));
        $grid->column('error', 'Error');
        $grid->column('paid_at', __('Paid at'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
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
        $show = new Show(OrderLog::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('order_id', __('Order id'));
        $show->field('admin_id', __('Admin id'));
        $show->field('no', __('No'));
        $show->field('payment_no', __('Payment no'));
        $show->field('remark', __('Remark'));
        $show->field('error', __('Error'));
        $show->field('paid_at', __('Paid at'));
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
        $form = new Form(new OrderLog());

        $form->number('order_id', __('Order id'));
        $form->number('admin_id', __('Admin id'));
        $form->text('no', __('No'));
        $form->text('payment_no', __('Payment no'));
        $form->text('remark', __('Remark'));
        $form->textarea('error', __('Error'));
        $form->text('paid_at', __('Paid at'));

        return $form;
    }
}
