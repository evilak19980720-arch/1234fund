<?php

namespace App\Admin\Controllers;

use App\Models\Win;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class WinController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '获奖名单';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Win());
        $grid->model()->where('admin_user_id', '=', Admin::user()->isRole('agents') ? Admin::user()->id : 0);
        $grid->column('id', __('Id'));
        $grid->column('phone', __('手机号'));
        $grid->column('amount', __('金额'))->display(function ($var) {
            return $var / 100;
        });
        $grid->column('show', __('显示'))->bool();
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
        $show = new Show(Win::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('phone', __('Phone'));
        $show->field('amount', __('Amount'));
        $show->field('show', __('Show'));
        $show->field('number', __('Number'));
        $show->field('sort', __('Sort'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('deleted_at', __('Deleted at'));


        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Win());

        $form->text('phone', __('手机号'));
        $form->number('amount', __('金额'));
        $form->switch('show', __('显示'));
        $form->hidden('admin_user_id')->value(Admin::user()->isRole('agents') ? Admin::user()->id : 0);
        $form->editing(function (Form $form) {
            $form->model()->amount = $form->model()->amount / 100;
        });
        $form->saving(function ($form) {
            $form->amount *= 100;
        });
        return $form;
    }
}
