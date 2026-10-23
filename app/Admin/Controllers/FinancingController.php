<?php

namespace App\Admin\Controllers;

use App\Models\Financing;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FinancingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '融资';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Financing());
        $grid->model()->with('user')
            ->orderBy('id', 'desc')
            ->paginate();
        $grid->column('id', __('Id'));
        $grid->column('user.name', '用户');

        $grid->column('amount', '金额')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('whatsapp', 'WS');
        $grid->column('project_name', '项目名称');
        $grid->column('project_plan', '项目计划');
        $grid->column('created_at', '发布时间');

        $grid->disableExport();
        $grid->disableFilter();
        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
        });

        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Financing());

        $form->editing(function ($form) {
            $form->model()->amount /= 100;
        });

        $form->text('user.name', '用户')->disable();
        $form->text('amount', '金额')->required();
        $form->text('whatsapp', 'WS')->required();
        $form->text('project_name', '项目名称')->required();
        $form->text('project_plan', '项目计划')->required();


        $form->saving(function ($form) {
            $form->amount *= 100;
        });

        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        return $form;
    }
}
