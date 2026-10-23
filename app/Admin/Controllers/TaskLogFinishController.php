<?php

namespace App\Admin\Controllers;

use App\Models\TaskLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\MessageBag;
use function Clue\StreamFilter\fun;

class TaskLogFinishController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '任务记录';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TaskLog());
        $grid->model()->with('task.type','user.vip')->with('user')
            ->orderBy('created_at','desc')
            ->paginate();
        $grid->disableCreateButton();
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        // 禁用筛选
        //$grid->disableFilter();

        $grid->disableActions();


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
        $grid->column('user.name', '账号');
        $grid->column('user.vip', '会员vip')->display(function ($var) {
            return $var['name'];
        });
        $grid->column('task.type', '任务类型')->display(function ($var) {
            return $var['name'];
        });
        $grid->column('task.title', '任务名')->style('width:25%');
        $grid->column('task.price', '任务价格')->display(function ($var) {
            return $var / 100;
        });
        $grid->img('图片')->lightbox(['width' => 50, 'height' => 50]);
        $grid->column('state','状态')->using([
            -2 => '放弃',
            -1 => '过期',
            0 => '进行中',
            1 => '待审核',
            2 => '成功',
            3 => '失败',
        ], '未知')->dot([
            -2 => 'gray',
            -1 => 'info',
            0 => 'primary',
            1 => 'warning',
            2 => 'success',
            3 => 'danger',
        ], 'warning');
        $grid->column('created_at', '提交时间');
        $grid->column('updated_at', '审核时间')->display(function ($var) {
            return in_array($this->state,[TaskLog::SUCCESS,TaskLog::FAIL]) ? $var : '';
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
        $show = new Show(TaskLog::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('task_id', __('Task id'));
        $show->field('img', __('Img'));
        $show->field('state', __('State'));
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
        $form = new Form(new TaskLog());
        $form->disableReset();
        $id = request()->route()->parameters()['task_log'];
        $state = TaskLog::where('id','=',$id)->value('state');
        $state == TaskLog::EXAMINE || $form->disableSubmit();

        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->text('task.title', '任务')->disable();
//        $form->textarea('task.content','任务内容')->readonly();
        $form->image('img', '图片');
        if ($state == TaskLog::EXAMINE) {
            $form->radio('state', '审核')->options(['2' => '成功', '3'=> '失败']);
        } else {
            $form->radio('state', '审核')->options(['2' => '成功', '3'=> '失败'])->disable();
        }

        return $form;
    }
}
