<?php

namespace App\Admin\Controllers;

use App\Models\Hierarchy;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\MessageBag;

class HierarchyController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hierarchy';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Hierarchy());

        $grid->column('id', 'Id');
        $grid->column('title', '层级名称');
        $grid->column('withdrawal_low_limit', '提现下限')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('withdrawal_limit', '提现上限')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '更新时间');
        $grid->actions(function ($actions) {
            if ($actions->getKey() == 1) {
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->disableView();
            }
        });
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->disableBatchActions();


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
        $form = new Form(new Hierarchy());
        $form->text('title', '层级名称');
        $form->number('withdrawal_low_limit', '提现下限');
        $form->number('withdrawal_limit', '提现上限');
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
        });
        $form->saving(function (Form $form) {
            $form->withdrawal_limit = $form->withdrawal_limit * 100;
            $form->withdrawal_low_limit = $form->withdrawal_low_limit * 100;
            if ($form->model()->id == 1) {
                $error = new MessageBag([
                    'title' => '错误',
                    'message' => '禁止修改默认层级!',
                ]);
                return back()->with(compact('error'));
            }
        });
        return $form;
    }
}
