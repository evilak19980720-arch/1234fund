<?php

namespace App\Admin\Controllers;

use App\Models\Type;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;

class TypeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '任务类型';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Type());
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        // 筛选
        $grid->quickSearch('name')->placeholder('输入类型名');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('vip_id', 'vip')->select('/api/v1/type_vips');
        });

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
        $grid->column('vip.name', 'Vip');
        $grid->column('name', '类型名称')->text();
        $grid->icon('图标')->lightbox(['width' => 50, 'height' => 50]);
//        $grid->column('commission', '佣金')->display(function ($var) {
//            return $var / 100;
//        });
        $grid->column('sort', '排序')->text();
        $grid->column('is_upload', '上传任务')->switch([
            'on' => ['value' => 1, 'text' => '√', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => '×', 'color' => 'default'],
        ]);
        $grid->column('single', '模式')->switch([
            'on' => ['value' => 1, 'text' => '单', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => '多', 'color' => 'default'],
        ]);
        $grid->column('state', '状态')->switch([
            'on' => ['value' => 1, 'text' => '启用', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => '禁用', 'color' => 'default'],
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
        $show = new Show(Type::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('is_upload', __('Is upload'));
        $show->field('mode', __('Mode'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Type());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->editing(function (Form $form) {
            $form->model()->commission = $form->model()->commission / 100;
        });

        $form->saving(function (Form $form) {
            $form->commission = $form->commission * 100;
        });

        $form->select('vip_id', 'vip')->options('/api/v1/type_vips')->required();
        $form->file('icon', '图标')->required();
        $form->text('name', '类型名称');
        $form->radio('single', '模式')->options([
            0 => '多任务', 1 => '单任务'
        ])->required();
        $form->switch('is_upload', '上传任务');
        $form->switch('state', '状态');
//        $form->currency('commission','佣金')->symbol('₹')->help('仅作展示');
        $form->number('sort', '排序')->required()->min(1)->help('从小到大');
        $form->UEditor('course', '教程');

        return $form;
    }

    public function getTypes()
    {
        return Type::select('id','name as text')->get();
    }
}
