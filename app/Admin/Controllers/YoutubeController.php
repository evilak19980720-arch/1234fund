<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Youtube\BatchImport;
use App\Admin\Actions\Youtube\Import;
use App\Models\Youtube;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

/**
 * 油管
 * @package App\Admin\Controllers
 */
class YoutubeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Youtube';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Youtube());
        $grid->model()->orderBy('id', 'desc')->paginate();

//        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        $grid->disableFilter();

        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->actions(function ($actions) {
            $actions->add(new Import());
            $actions->disableView();
        });

        $grid->batchActions(function ($batch) {
            $batch->add(new BatchImport());
        });

        $grid->column('id', __('Id'));
        $grid->column('title', '标题')->display(function ($var) {
            return mb_strlen($var) > 30 ?
                sprintf('%s...', mb_substr($var, 0, 30)) :
                $var;
        });
        $grid->column('imported', '导入')->display(function ($var) {
            return $var ? '<span class="text-success fa fa-check"><span>' : '<span class="text-danger fa fa-close"><span>';
        });;
        $grid->column('link', '链接')->link();
        $grid->column('people', '收看次数')->display(function ($var) {
            return mb_substr($var, 5);
        });
        $grid->column('author', '作者');
        $grid->column('created_at', '采集时间');

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
        $show = new Show(Youtube::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('title', __('Title'));
        $show->field('link', __('Link'));
        $show->field('people', __('People'));
        $show->field('author', __('Author'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Youtube());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->text('title', '标题');
        $form->url('link', '链接');
        $form->text('people', '收看次数');
        $form->text('author', '作者');

        return $form;
    }
}
