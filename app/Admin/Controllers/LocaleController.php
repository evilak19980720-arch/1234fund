<?php

namespace App\Admin\Controllers;

use App\Models\Locale;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;

class LocaleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '语言管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Locale());

        $query = $grid->model()->orderBy('sort');
        $grid->paginate(100);
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        $grid->disableFilter();
        // 筛选
        $grid->quickSearch('code','viewname','name');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
            //$actions->disableDelete();
        });

        $grid->column('id', __('Id'));
        $grid->column('code', '编码');
        $grid->column('viewname', '显示名称');
        $grid->column('name', '语言名称');
        $grid->column('sort', '排序')->text();
        $grid->column('state', '状态')->switch([
            'on' => ['value' => 1, 'text' => '启用', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => '禁用', 'color' => 'default'],
        ]);

        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Locale());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->text('code', '编码')->required();
        $form->text('viewname', '显示名称')->required();
        $form->text('name', '语言名称')->required();
        $form->number('sort', '排序')->required()->default(0)->min(0)->help('从小到大');
        $form->switch('state', '状态')->default(1)->required();

        return $form;
    }
}
