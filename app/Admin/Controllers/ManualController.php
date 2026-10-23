<?php

namespace App\Admin\Controllers;

use App\Jobs\GoogleTrans;
use App\Models\Manual;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

/**
 * 手册
 * @package App\Admin\Controllers
 */
class ManualController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '手册';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Manual());
        $grid->disableRowSelector();
        $grid->disableExport();
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

        $grid->quickSearch('title')->placeholder('输入标题');

        $grid->column('id', __('Id'));
        $grid->column('title', '标题')->display(function ($var){
            return $var . "[<a href='/admin/trans-admin?key=manuals.title.{$this->id}' target='_blank'>多语言</a>]";
        });
        $grid->column('content', '内容')->display(function (){
            return "[<a href='/admin/trans-admin?key=manuals.content.{$this->id}.html' target='_blank'>多语言</a>]";
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
        $show = new Show(Manual::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('title', __('Title'));
        $show->field('content', __('Content'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Manual());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->text('title', '标题');
        $form->UEditor('content', '内容')->required();

        $form->radio('autotrans', '自动翻译')
            ->options([
                1 => '是',
                0 => '否',
            ])->default(1);

        $form->ignore(['autotrans']);

        $form->saved(function (Form $form) {
            $id = $form->model()->id;
            $autotrans = \request('autotrans');
            if($autotrans == 1){
                $key = "manuals.title.".$id;
                $value = $form->title;
                GoogleTrans::dispatch(2, $key, $value, "")->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                //app(Tran::class)->tran(2, $key, $value, "");

                $key = "manuals.content.".$id.".html";
                $value = $form->content;
                GoogleTrans::dispatch(2, $key, $value, "")->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                //app(Tran::class)->tran(2, $key, $value, "");
            }
        });

        return $form;
    }
}
