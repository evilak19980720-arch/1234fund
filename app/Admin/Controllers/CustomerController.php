<?php

namespace App\Admin\Controllers;

use App\Models\Customer;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;

class CustomerController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '客服';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Customer());
        $grid->model()->with('admin')->orderBy('id', 'desc')->paginate();
        if (Admin::user()->isRole('agents')) {
            $grid->model()->where('admin_user_id', Admin::user()->id);
        }
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
        });

        $grid->column('id', __('Id'));
        if (Admin::user()->isRole('administrator')) {
            $grid->column('admin.name', '代理');
        }
        $grid->column('contact', '联系方式');
        $grid->column('forbid', '状态')->display(function ($value) {
            return Customer::FORBID_MAP[$value];
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
        $show = new Show(Customer::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('icon', __('Icon'));
        $show->field('platform', __('Platform'));
        $show->field('contact', __('Contact'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Customer());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->select('type', '平台')->options(Customer::TYPE_MAP)->required();
        $form->text('contact', '联系方式')->required();
        $form->switch('forbid', '状态')->states([
            'on' => ['value' => 1, 'text' => '禁用', 'color' => 'danger'],
            'off' => ['value' => 0, 'text' => '启用', 'color' => 'success'],
        ]);

        if (Admin::user()->isRole('administrator')) {
            $form->select('admin_user_id', '代理')->options($this->admin_user());
        }
        if (Admin::user()->isRole('agents')) {
            $form->hidden('admin_user_id')->value(Admin::user()->id);
        }

        return $form;
    }

    public function admin_user()
    {
        $data = Administrator::query()->whereHas('roles', function ($query) {
            $query->where('slug', 'agents');
        })
            ->pluck('name', 'id')->toArray();
        $data[0] = '无代理';
        ksort($data);
        return $data;
    }



}
