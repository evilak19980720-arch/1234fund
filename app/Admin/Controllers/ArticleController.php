<?php

namespace App\Admin\Controllers;

use App\Jobs\GoogleTrans;
use App\Models\Article;
use App\Models\Locale;
use App\Models\Tran;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;

class ArticleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '文章';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Article());
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        // 禁用筛选
        $grid->filter(function ($filter) {
            $filter->equal('key', '类型')->select(Article::ARTICLE_OPTIONS);
        });

        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->column('id', __('Id'));
        $grid->column('key', '类型')->display(function ($val){
            return Article::ARTICLE_OPTIONS[$val];
        });
        $grid->column('sort', '排序值');

        $grid->column('title', '标题')->display(function ($var){
            return $var . "[<a href='/admin/trans-admin?key=articles.title.{$this->id}' target='_blank'>多语言</a>]";
        });
        $grid->column('content', '内容')->display(function (){
            return "[<a href='/admin/trans-admin?key=articles.content.{$this->id}.html' target='_blank'>多语言</a>]";
        });
        $grid->column('created_at', '创建时间');


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
        $show = new Show(Article::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('key', __('Key'));
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
        $form = new Form(new Article());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
        if (Route::currentRouteName() == 'admin.articles.edit') {
            $form->text('key', '关键词')->required()->readonly();
        }else{
            $form->select('key','类型')->options(Article::ARTICLE_OPTIONS)->required();
        }
        $form->text('title', '标题')->required();
        $form->UEditor('content', __('内容'))->required();
        $form->number('sort','排序值')->default(0)->max(100);
        if($form->isCreating()){
            $form->hidden('page_views','浏览量')->default(rand(10000,99999));
        }

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
                $key = "articles.title.".$id;
                $value = $form->title;
                GoogleTrans::dispatch(2, $key, $value, "")->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                //app(Tran::class)->tran(2, $key, $value, "");

                $key = "articles.content.".$id.".html";
                $value = $form->content;
                GoogleTrans::dispatch(2, $key, $value, "")->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                //app(Tran::class)->tran(2, $key, $value, "");
            }
        });

        return $form;
    }
}
