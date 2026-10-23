<?php

namespace App\Admin\Controllers;


use App\Models\CollectTrctask;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Admin\Actions\CollectTrctask\ViewAddress;
use App\Admin\Actions\CollectTrctask\StartTransfer;

class CollectTrctaskController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '归集任务TRC';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CollectTrctask());
        $grid->model()->withCount('collect_trctask_logs')->orderBy('id', 'desc')->paginate();
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        // 禁用筛选
        $grid->disableFilter();

        //禁用创建按钮
        $grid->disableCreateButton();
        //$grid->disableActions();
        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();

            $actions->add(new ViewAddress());
            $actions->add(new StartTransfer());

        });
        //$grid->quickSearch('address','user.name')->placeholder('输入TRC地址');

        $grid->column('id', __('Id'));
        $grid->column('type', '类型')->display(function ($var){
            return CollectTrctask::TYPE_ARR[$var];
        });
        $grid->column('toaddress', '收款地址');
        $grid->column('state', '状态')->display(function ($var){
            return CollectTrctask::STATE_ARR[$var];
        });
        $grid->column('collect_trctask_logs_count', '归集数量');
        $grid->column('collect_trctask_logs_sum', '成功金额')->display(function ($var){
            return $this->collect_trctask_logs->where('state','=',8)->sum('balance');
        });
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '更新时间');
        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CollectTrctask());

        return $form;
    }


}
