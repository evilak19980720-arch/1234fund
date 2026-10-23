<?php

namespace App\Admin\Controllers;

use App\Models\Interest;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

/**
 * 利息宝
 * @package App\Admin\Controllers
 */
class InterestController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '利息宝会员';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Interest());
        $grid->model()->with('user')->orderBy('id','desc')->paginate();

        $grid->disableCreateButton();
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        // 筛选
        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->like('user.name', '会员');

        });

        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->disableActions();

        $grid->column('id', __('Id'));
        $grid->column('user.name', '会员');
        $grid->column('balance', '余额')->display(function ($balance) {
            return $balance/100;
        });
        $grid->column('confirm_balance', '确认金额')->display(function ($balance) {
            return $balance/100;
        });
        $grid->column('revenue_switch', '收益开关')->switch([
            'on'  => ['value' => 1, 'text' => '打开', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => '关闭', 'color' => 'default'],
        ]);

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
        $show = new Show(Interest::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('balance', __('Balance'));
        $show->field('confirm_balance', __('Confirm balance'));
        $show->field('revenue_switch', __('Revenue switch'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Interest());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->number('user_id', __('User id'));
        $form->decimal('balance', __('Balance'))->default(0.00);
        $form->decimal('confirm_balance', __('Confirm balance'))->default(0.00);
        $form->switch('revenue_switch', __('Revenue switch'))->default(1);

        return $form;
    }
}
