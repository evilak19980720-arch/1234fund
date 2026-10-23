<?php

namespace App\Admin\Controllers;

use App\Models\AdminUser;
use App\Models\Complaint;
use App\Models\Reply;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Displayers\Actions;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

/**
 * 投诉
 * @package App\Admin\Controllers
 */
class ComplaintController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '投诉建议';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Complaint());
        $grid->model()->with('user')
            ->whereHas('user',function($sql){
                $sql->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0);
            })
            ->orderBy('complaints.created_at', 'desc')
            ->paginate();

        // 禁用创建
        $grid->disableCreateButton();
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
            $actions->disableDelete();
        });

        $grid->setActionClass(Actions::class);

        $grid->column('id', __('Id'));
        $grid->column('type', '类型')->display(function ($var) {
            return $var === 'complaint' ? '投诉' : '举报';
        })->label();
        $grid->column('title', '标题')->limit(25);
        $grid->column('user.name', '用户名');
        $grid->column('contact', '联系方式');
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
        $show = new Show(Complaint::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user.name', '用户');
        $show->field('type', '类型');
        $show->field('title', '标题');
        $show->field('content', '内容');
        $show->field('contact', '联系方式');
        $show->field('created_at', '创建时间');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Complaint());
        $form->disableSubmit();
        $form->disableReset();
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->text('type', '类型')->readonly();
        $form->text('title', '标题')->readonly();
        $form->textarea('content', '内容')->readonly();
        $form->text('contact', '联系方式')->readonly();

        return $form;
    }

    public function edit($id, Content $content)
    {
        return $content
            ->title('投诉')
            ->description('投诉举报')
            ->row($this->form()->edit($id))
            ->row(Admin::grid(Reply::class, function (Grid $grid) use ($id) {

                $grid->disableExport();
                $grid->disableFilter();
                $grid->disableRowSelector();
                $grid->disableActions();

                $grid->setName('replies')
                    ->setTitle('回复')
                    ->setRelation(Complaint::find($id)->replies()->orderBy('created_at', 'desc'))
                    ->resource('/admin/replies');

                $grid->id();
                $grid->column('user', '回复人')->display(function () {
                    if ($this->user_id) {
                        return sprintf('<span class="text-danger">%s</span>', User::where('id', '=', $this->user_id)->value('name'));
                    }
                    if ($this->admin_user_id) {
                        return sprintf('<span class="text-success">%s</span>', AdminUser::where('id', '=', $this->admin_user_id)->value('name'));
                    }
                    return '';
                });
                $grid->content('内容')->limit(50);
                $grid->created_at('回复时间');

            }));
    }
}
