<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Youtube\BatchTypeImport;
use App\Admin\Actions\Youtube\TypeImport;
use App\Models\VideoType;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class VidedoTypeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '视频分类';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VideoType());
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
            $actions->add(new TypeImport());
        });

        $grid->batchActions(function ($batch) {
            $batch->add(new BatchTypeImport());
        });



        $grid->column('id', __('Id'));
        $grid->column('name', '类型');
//        $grid->column('price', '价格')->display(function ($var) {
//            return $var / 100;
//        });

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
        $show = new Show(VideoType::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
//        $show->field('price', __('Price'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new VideoType());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->text('name', '类型');
//        $form->currency('price','价格')->symbol('₹');

//        $form->editing(function (Form $form) {
//            $form->model()->price = $form->model()->price / 100;
//        });

//        $form->saving(function (Form $form) {
//            $form->price = $form->price * 100;
//        });

        return $form;
    }

    public function getTypes()
    {
        return VideoType::select('id','name as text')->get();
    }
}
