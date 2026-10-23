<?php

namespace App\Admin\Controllers;

use App\Models\Account;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AccountController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '用户账变';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Account());
        $admin_user_id = Admin::user()->isRole('agents') ? Admin::user()->id : 0;

        $grid->model()->with('fromUser', 'toUser')
            ->where(function (Builder $builder) use ($admin_user_id) {
                $builder->whereIn('to', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                });
                $builder->orWhereIn('from', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                });

            })
            ->whereNotIn('type',[17])
            ->orderBy('created_at', 'desc')
            ->paginate();

        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        $grid->disableCreateButton();

        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->disableActions();


        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->where(function ($query) {
                $query->where('accounts.id', '=', $this->input);
            }, 'ID');
            $filter->equal('order.no', '订单号');
            $filter->like('toUser.name', '收款人');
            $filter->like('fromUser.name', '发起人');
            $ids = [0,1,3,8,9,10,11,13,16,18,21,23,26,27,28,29,30];
            foreach ($ids as $id){
                $data[$id] = transtable('account_type.'.$id, 'zh-CN');
            }
            $filter->equal('type', '类型')->select($data);
            $filter->equal('level', '等级')->select([
                0 => '无',
                1 => '第一级',
                2 => '第二级',
                3 => '第三级',
            ]);
        });

        $grid->column('id', __('Id'));
        $grid->column('order.no', '订单号');
        $grid->column('type', '类型')->display(function ($var) {
            return transtable('account_type.'.$var  , 'zh-CN');
        });
        $grid->column('toUser.name', '收款人');
        $grid->column('fromUser.name', '发起人');
        $grid->column('amount', '金额')->display(function ($var) {
            return $var > 0 ?
                sprintf('<span class="text-success">%s</span>', $var / 100) :
                sprintf('<span class="text-danger">%s</span>', $var / 100);
        });
        $grid->column('level', '等级');
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '完成时间');

        return $grid;
    }
}
