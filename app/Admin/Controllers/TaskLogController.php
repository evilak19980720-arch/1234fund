<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Task\BatchAudit;
use App\Models\Account;
use App\Models\Rule;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;
use App\Models\Vip;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use function Clue\StreamFilter\fun;

class TaskLogController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '任务审核';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        Admin::js('/js/task.js');


        $grid = new Grid(new TaskLog());

        $grid->batchActions(function ($batch) {
            $batch->add(new BatchAudit());
        });

        $grid->model()->with(['task.crowd', 'user.vip'])->with('user')
            ->where('state', '=', TaskLog::EXAMINE)
            ->orderBy('state', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate();
        $grid->disableCreateButton();
        // 禁用导出
        $grid->disableExport();
        // 禁用筛选
        $grid->disableFilter();

        $grid->tools(function ($tools) {
//            $tools->append('<a href="javascript:;" class="btn btn-sm btn-success" title="批量成功" id="pass"><span class="hidden-xs">&nbsp;&nbsp;批量审核</span></a>');
//            $tools->append('<a href="javascript:;" class="btn btn-sm btn-danger" title="批量失败" id="refuse"><span class="hidden-xs">&nbsp;&nbsp;批量审核</span></a>');
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
        $grid->column('state', '状态')->using([
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
            return in_array($this->state, [TaskLog::SUCCESS, TaskLog::FAIL]) ? $var : '';
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
        $state = TaskLog::where('id', '=', $id)->value('state');
        $state == TaskLog::EXAMINE || $form->disableSubmit();

        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->editing(function (Form $form) {
            $form->model()->task->price = $form->model()->task->price / 100;
            $form->model()->user_vip = $form->model()->user->vip->name;
            $form->model()->task_type = $form->model()->task->type->name;
        });

        $form->text('user.name', '账号')->disable();
        $form->text('user_vip', '会员vip')->disable();
        $form->text('task_type', '任务类型')->disable();
        $form->text('task.title', '任务')->disable();
        $form->currency('task.price','任务单价')->symbol('₹')->disable();
        $form->image('img', '图片');
        if ($state == TaskLog::EXAMINE) {
            $form->radio('state', '审核')->options(['2' => '成功', '3' => '失败']);
        } else {
            $form->radio('state', '审核')->options(['2' => '成功', '3' => '失败'])->disable();
        }

        $form->saved(function (Form $form) {
            // 收益计算
            if ($form->model()->state == TaskLog::SUCCESS) {
                (new TaskLog())->income($form->model()->user_id,$form->model()->task_id);
            }
        });

        return $form;
    }
}
