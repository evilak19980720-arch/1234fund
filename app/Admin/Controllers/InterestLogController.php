<?php

namespace App\Admin\Controllers;

use App\Models\InterestLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class InterestLogController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '账变记录';


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new InterestLog());
        $grid->model()->with('user')->orderBy('created_at', 'desc')->paginate();

        $grid->disableCreateButton();
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        // 禁用筛选
//        $grid->disableFilter();

        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->disableActions();

        $grid->filter(function ($filter) {

            // 去掉默认的id过滤器
            $filter->disableIdFilter();

            $filter->like('user.name', '会员');
            $filter->between('created_at', '时间')->datetime();

        });

        $grid->column('id', __('Id'));
        $grid->column('user.name', '会员');
        $grid->column('amount', '金额')->display(function ($var) {
            return $var > 0 ?
                '<span class="text-success">'. $var/100 .'</span>':
                '<span class="text-danger">'. $var/100 .'</span>';
        });
        $grid->column('balance', '余额')->display(function ($balance) {
            return $balance/100;
        });
        $grid->column('created_at', '时间');
        $grid->column('confirm','确认')->bool();

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
        $show = new Show(InterestLog::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('amount', __('Amount'));
        $show->field('balance', __('Balance'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('confirm', __('Confirm'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new InterestLog());

        $form->number('user_id', __('User id'));
        $form->decimal('amount', __('Amount'));
        $form->decimal('balance', __('Balance'))->default(0.00);
        $form->switch('confirm', __('Confirm'));

        return $form;
    }
}
