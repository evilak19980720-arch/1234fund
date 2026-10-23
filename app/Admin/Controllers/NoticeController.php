<?php

namespace App\Admin\Controllers;

use App\Jobs\GoogleTrans;
use App\Models\Notice;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class NoticeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '公告';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Notice());
        $grid->model()
            ->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)
            ->orderBy('top','desc')->orderBy('created_at','desc')
            ->select('id','title','content','eject','top','created_at')->paginate();
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        // 禁用筛选
        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->like('title', '标题');
            $filter->between('created_at', '时间')->datetime();

        });

        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->column('id', __('Id'));
        $grid->column('top', '置顶')->display(function ($var) {
            return $var ? "<i class='fa fa-check' style='color:green'></i>" : '';
        });
        $grid->column('eject', '弹出')->display(function ($var) {
            return $var ? "<i class='fa fa-check' style='color:green'></i>" : '';
        });
        $grid->column('title', '标题')->display(function ($var){
            return $var . "[<a href='/admin/trans-admin?key=notices.title.{$this->id}' target='_blank'>多语言</a>]";
        });
        $grid->column('content', '内容')->display(function (){
            return "[<a href='/admin/trans-admin?key=notices.content.{$this->id}.html' target='_blank'>多语言</a>]";
        });
        $grid->column('created_at', '发布时间');

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
        $show = new Show(Notice::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('title', '标题');
        $show->field('content', '内容');
        $show->field('created_at', __('Created at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Notice());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->text('title', '标题')->required();
        $form->UEditor('content', '内容')->required();
        $form->switch('top','置顶');
        $form->switch('eject','弹出')->help('弹出公告必须设置置顶');

        $form->radio('autotrans', '自动翻译')
            ->options([
                1 => '是',
                0 => '否',
            ])->default(1);

        $form->ignore(['autotrans']);

        if (Admin::user()->isRole('administrator')) {
            $form->hidden('admin_user_id')->value(0);
        }
        if (Admin::user()->isRole('agents')) {
            $form->hidden('admin_user_id')->value(Admin::user()->id);
        }

        $form->saved(function (Form $form) {
            $id = $form->model()->id;
            $autotrans = \request('autotrans');
            if($autotrans == 1){
                $key = "notices.title.".$id;
                $value = $form->title;
                GoogleTrans::dispatch(2, $key, $value, "")->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                //app(Tran::class)->tran(2, $key, $value, "");

                $key = "notices.content.".$id.".html";
                $value = $form->content;
                GoogleTrans::dispatch(2, $key, $value, "")->onQueue(env('REDIS_QUEUE_LOW', 'default'));
                //app(Tran::class)->tran(2, $key, $value, "");
            }
        });

        return $form;
    }
}
