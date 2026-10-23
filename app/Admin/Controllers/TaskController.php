<?php

namespace App\Admin\Controllers;

use App\Handlers\KeysHandler;
use App\Models\Task;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Redis;
use function Clue\StreamFilter\fun;

class TaskController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '任务';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Task());
        $grid->model()->orderBy('id', 'desc')->paginate();

        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        // 筛选
        $grid->quickSearch('title')->placeholder('输入任务标题');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('vip_id', 'vip')->select('/api/v1/type_vips');
            $filter->equal('number', '数量');
            $filter->equal('expire', '过期时间');
            $filter->between('price', '价格');
            //$filter->between('start', '开始时间')->datetime();
            $filter->between('end', '结束时间')->datetime();
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
        $grid->column('type.name', '类型')->display(function ($var) {
            return $this->type->single ? '<strong class="text-warning">' . $var . '</strong>' : $var;
        });
        $grid->column('status', '状态')->bool();
        $grid->column('title', '标题')->display(function ($var) {
            if (strlen($var) > 30) {
                return sprintf('%s...', mb_substr($var, 0, 30));
            }
            return $var;
        });
        $grid->column('number', '数量')->display(function ($var) {
            return sprintf('%d/%d', $var, $this->finished);
        });
        $grid->column('price', '价格')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('expire', '过期时间(分钟)');
        //$grid->column('start', '开始时间')->datetime();
        $grid->column('end', '结束时间');
        $grid->column('automatic_audit', '自动审核')->bool();

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
        $show = new Show(Task::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('type_id', __('Type id'));
        $show->field('title', __('Title'));
        $show->field('number', __('Number'));
        $show->field('price', __('Price'));
        $show->field('content', __('Content'));
        //$show->field('start', __('Start'));
        $show->field('end', __('End'));
        $show->field('automatic_audit', __('Automatic audit'));
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
        $form = new Form(new Task());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->editing(function (Form $form) {
            $form->model()->price = $form->model()->price / 100;
        });

        $form->select('type_id', '任务类型')->options('/api/v1/task_types')->required();
        $form->text('title', '任务标题')->required();
        $form->number('number', '任务数量')->required();
        $form->currency('price', '任务单价')->symbol('₹')->help('如果不设置单价则为领取任务的人的vip薪水');
        $form->UEditor('content', '内容')->required();
        $form->number('expire', '过期时间')->required()->min(1)->help('单位：分，领取任务后在指定时间内未完成则任务过期');
        //$form->datetime('start', '开始时间')->default(date('Y-m-d H:i:s'))->required();
        $form->datetime('end', '结束时间')->default(date('Y-m-d H:i:s'))->required();
        $form->switch('automatic_audit', '自动审核');
        $form->switch('status', '状态');

        $form->saving(function (Form $form) {
            $form->price = $form->price * 100;
        });

        $form->saved(function (Form $form) {

            $data = [
                'id' => $form->model()->id,
                'type_id' => $form->model()->type_id,
                'title' => $form->model()->title,
                'number' => $form->model()->number,
                'price' => $form->model()->price,
                'content' => $form->model()->content,
                'start' => $form->model()->start,
                'end' => $form->model()->end,
                'expire' => $form->model()->expire,
                'automatic_audit' => $form->model()->automatic_audit,
                'status' => $form->model()->status,
                'created_at' => $form->model()->created_at,
                'updated_at' => $form->model()->updated_at,
            ];

            Redis::multi();
            $seconds = Carbon::parse($form->model()->end)->diffInSeconds(Carbon::now());
            // 缓存任务
            $key1 = sprintf(KeysHandler::TASK, $form->model()->id);
            if ($form->isCreating()) {
                $data['finished'] = 0;
            }
            if ($form->isEditing()) {
                Redis::hset($key1, 'finished', $form->model()->finished);
            }

            Redis::hmset($key1, $data);
            Redis::expire($key1, $seconds);
            // 创建任务领取
            $key2 = sprintf(KeysHandler::TASK_USERS, $form->model()->id);
            Redis::zadd($key2, 0, 0);
            Redis::expire($key2, $seconds);
            Redis::exec();
        });

        return $form;
    }
}
