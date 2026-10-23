<?php

namespace App\Admin\Controllers;


use App\Models\CollectTrctask;
use App\Models\CollectTrctaskLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Admin\Actions\CollectTrctask\StartTransfer;

class CollectTrctaskLogController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '归集任务详情';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CollectTrctaskLog());
        $grid->model()->with('collect_trctask')->orderBy('id', 'asc')->paginate();
        $grid->paginate(500);
        // 禁用CheckBox
        //$grid->disableRowSelector();
        // 禁用导出
        //$grid->disableExport();
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
        });
        $grid->quickSearch('address')->placeholder('归集地址');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('collect_trctask_id');
            //$filter->like('address', '归集地址');
        });
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append("<a href='/admin/collect-trctask' class='report-posts btn btn-sm btn-danger'>返回</a>");
        });


        $grid->column('id', __('Id'));
        $grid->column('collect_trctask_id', '归集任务ID');
        $grid->column('collect_trctask.type', '类型')->display(function ($var){
            return CollectTrctask::TYPE_ARR[$var];
        });
        $grid->column('address', '归集地址');
        $grid->column('collect_trctask.toaddress', '收款地址');
        $grid->column('state', '状态')->display(function ($var){
            return CollectTrctaskLog::STATE_ARR[$var];
        });
        $grid->column('start_at', '开始时间');
        $grid->column('balance', '转账金额');
        $grid->column('transaction_id', '交易单号');
        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CollectTrctaskLog());

        return $form;
    }


}
