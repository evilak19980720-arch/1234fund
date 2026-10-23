<?php

namespace App\Admin\Controllers;

use App\Models\AgencyContest;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;

class AgencyContestController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '活动列表';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AgencyContest());
        $grid->model()->with('agency_contest_users','agency_contest_first_user')->orderBy('id', 'desc')->paginate();
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
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
        $grid->column('issue', '期号');
        $grid->column('start_time', '开始时间');
        $grid->column('end_time', '结束时间');
        $grid->column('reward', '奖金')->display(function ($var){
            return $var / 100;
        });
        $grid->column('first_user', '当前第一')->display(function ($var){
            if(!empty($this->agency_contest_first_user)){
                return $this->agency_contest_first_user->user_phone;
            }else{
                return "";
            }
        });
        $grid->column('status', '状态')->display(function ($value) {
            return AgencyContest::STATE_MAP[$value];
        });
        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AgencyContest());
        $form->tools(function (Form\Tools $tools) {
            // 去掉`查看`按钮
            $tools->disableView();
            // 去掉`删除`按钮
            $tools->disableDelete();
        });
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
        $form->editing(function (Form $form) {
            $form->model()->reward = $form->model()->reward / 100;
        });
        $form->text('issue', '期号')->required();
        $form->currency('reward', '奖金')->required()->symbol('₹');
        $form->select('status', '状态')->options(AgencyContest::STATE_MAP)->default(1)->required();
        $form->datetime('start_time', '开始时间')->default(date('Y-m-d H:i:s'))->required();
        $form->datetime('end_time', '结束时间')->default(date('Y-m-d H:i:s'))->required();

        $form->saving(function (Form $form) {
            if ($form->reward) {
                $form->reward = $form->reward * 100;
            }
        });

        return $form;
    }
}
