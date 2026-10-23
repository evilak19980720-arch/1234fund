<?php

namespace App\Admin\Controllers;

use App\Models\Problem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\MessageBag;

class ProblemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '问题列表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Problem());

        $grid->column('id', 'Id');
        $grid->column('title', '问题');
        $grid->column('keyword', '关键字');
        $grid->column('hits', '点击');
        $grid->column('hot', '推荐')->bool();
        $grid->column('withdraw', '提现')->bool();
        $grid->column('reply', '回复')->width(300);

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->disableBatchActions();
        $grid->disableFilter();


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
        $show = new Show(Hierarchy::findOrFail($id));


        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Problem());

        $form->text('title', '问题');
        $form->text('keyword', '关键字');
        $form->text('reply', '回复');

        $form->number('hits', '点击')->default(0);

        $form->switch('hot', __('推荐'))->default(1);
        $form->switch('withdraw', __('提现'))->default(1);

        return $form;
    }
}
