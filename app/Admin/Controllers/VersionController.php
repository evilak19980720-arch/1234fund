<?php

namespace App\Admin\Controllers;

use App\Models\Version;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;

class VersionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '版本';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Version());
        $grid->model()->orderBy('version', 'desc');

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
        });

        // 搜索
        $grid->quickSearch('version')->placeholder('输入版本号搜索');
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
        });

        $grid->column('id', __('Id'));
        $grid->column('version', __('版本号'));
        $grid->column('version_num', __('版本号数字'));
        $grid->type('类型')->display(function ($var) {
            return $var == 'ios' ? "<i class='fa fa-apple' style='color:red'></i>" : "<i class='fa fa-android' style='color:green'></i>";
        });
        $grid->column('path', __('地址'))->display(function ($var) {
            if (strpos($var, 'http') === false) {
                $var = aliyun_domain() . '/' . $var;
            }
            return $var;
        });
        $grid->column('force', __('强制更新'))->display(function ($var) {
            return $var ? "<i class='fa fa-check' style='color:green'></i>" : "<i class='fa fa-times' style='color:red'></i>";
        });
        $grid->column('created_at', __('发布时间'))->date('Y-m-d');;

        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Version());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });
        $flag = request()->route()->parameters() ? true : false;
        if ($flag) {
            $form->radio('type', '类型')->options(['android' => 'android', 'ios' => 'ios'])->disable();
            $form->text('version', '版本号')->required();
        } else {
            $form->radio('type', '类型')->options(['android' => 'android', 'ios' => 'ios'])->required();
            $form->text('version', '版本号')->required();
        }
        $form->text('version_num', '版本号数字')->required();
        $form->text('path', '下载地址');
        $form->file('apk', '上传安装包')->name(function ($file) {
            return 'apk/' . date("Ym/d", time()) . '_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        });

        $form->textarea('explain', __('说明'))->required();
        $form->switch('force', __('强制更新'));

        return $form;
    }
}
