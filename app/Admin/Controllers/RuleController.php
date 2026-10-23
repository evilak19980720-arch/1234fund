<?php

namespace App\Admin\Controllers;

use App\Models\Rule;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use function Clue\StreamFilter\fun;

class RuleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '分销等级';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Rule());
        $grid->disableCreateButton();
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        // 禁用筛选
        $grid->disableFilter();

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
        $grid->column('type', '类型')->display(function ($var) {
            return $var ? '<span class="text-success">分销</span>' : '<span class="text-warning">充值</span>';
        });
        $grid->column('level', '等级')->display(function ($var) {
            switch ($var) {
                case 1:
                    return '<span class="text-danger">' . $var . '</span>';
                    break;
                case 2:
                    return '<span class="text-warning">' . $var . '</span>';
                    break;
                case 3:
                    return '<span class="text-aqua">' . $var . '</span>';
                    break;
            }
        });
        $grid->column('percent', '利润返利比例')->display(function ($var) {
            return $var . '%';
        });
        $grid->column('rebate_percent', '购买返利比例')->display(function ($var) {
            return $var . '%';
        });

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
        $show = new Show(Rule::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('type', __('Type'));
        $show->field('level', __('Level'));
        $show->field('percent', __('Percent'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Rule());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->radio('type', '类型')->options([1 => '分销收入']);//0 => '充值收入',
        $form->number('level', '分销等级')->min(1)->max(3)->help('1-3级');
        $form->rate('percent', '利润返利比例');
        $form->rate('rebate_percent', '购买返利比例');

        return $form;
    }
}
