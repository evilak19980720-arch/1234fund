<?php

namespace App\Admin\Controllers;

use App\Models\AgencyContest;
use App\Models\AgencyContestUser;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;

class AgencyContestFakeUsersController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '虚拟用户';
    public $rank_arr;

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AgencyContestUser());
        $grid->model()->with('agency_contest')->where('user_id','=',0)->orderBy('id', 'desc')->paginate();
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
        $grid->column('agency_contest.issue', '期号');
        $grid->column('user_phone', '会员');


        $rank_arr = [];
        $grid->column('rank', '排名')->display(function ($var){
            $rank_user = AgencyContestUser::getAllUserRank($this->agency_contest_id, $this->id);
            if($rank_user){
                return  $rank_user->rank;
            }

            return '未知';
        });
        $grid->column('active_member_count', '有效会员');
        $grid->column('accumulated_commission', '佣金')->display(function ($var){
            return $var / 100;
        });
        $grid->column('dayadd_active_member_count', '每日新增用户基数');
        $grid->column('dayadd_accumulated_commission', '每日新增佣金基数');
        $grid->column('whatsapp', 'WS');
        $grid->column('telegram', 'TG');


        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AgencyContestUser());
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

        $form->select('agency_contest_id', '活动选择')->options($this->getAgencyContest())->required();
        $form->number('dayadd_active_member_count', '每日新增用户基数')->required();
        $form->number('dayadd_accumulated_commission', '每日新增佣金基数')->required();
        $form->text('user_phone')->required();

        $form->text('whatsapp', 'WS');
        $form->text('telegram', 'TG');

        $form->saving(function (Form $form) {
            //if($form->isCreating()){
                //$form->user_phone = '8801'.rand(100000000,999999999);
            //}
        });

        return $form;
    }

    public function getAgencyContest(){
        $list = AgencyContest::query()->select(['id','issue'])
            ->where('end_time','>',now())->where('status',1)->get();
        $arr = [];
        foreach ($list as $val){
            $arr[$val->id] = '['.$val->id.']'.$val->issue;
        }
        return $arr;
    }


}
