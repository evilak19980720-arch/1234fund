<?php

namespace App\Admin\Controllers;

use App\Models\Pixcel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;

class PixcelController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '像素管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Pixcel());

        //$query = $grid->model();
        $grid->paginate(100);
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        $grid->disableFilter();
        // 筛选
        $grid->quickSearch('host','pixcel');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
            //$actions->disableDelete();
        });

        $grid->column('id', __('Id'));
        $grid->column('host', '域名');
        $grid->column('pixcel', '像素ID');
        $grid->column('tkpixcel', 'TK像素ID');
        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Pixcel());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
        $form->text('host', '域名')->required();
        $form->text('pixcel', '像素ID')->required();
        $form->text('tkpixcel', 'TK像素ID')->required();

        return $form;
    }


}
