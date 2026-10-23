<?php

namespace App\Admin\Controllers;

use App\Models\AdminUser;
use App\Models\AgentConf;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AgentConfController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '代理配置';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AgentConf());
        $grid->model()->select('agent_conf.domain','agent_conf.join_group_link','agent_conf.h5_link','agent_conf.join_group_str','admin_users.id','admin_users.name','admin_users.username')
            ->rightjoin('admin_users', function ($join) {
                $join->on('admin_users.id', '=', 'agent_conf.admin_user_id');
            })
            ->join('admin_role_users', function ($join) {
                $join->on('admin_role_users.user_id', '=', 'admin_users.id')->where('role_id','=',3)->where('admin_users.id','<>',6);
            });

        if (Admin::user()->isRole('agents')) {
            $grid->model()->where('admin_user_id','=',Admin::user()->id);
        }

        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->disableFilter();
        $grid->disableCreateButton();

        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });

        $grid->column('id', __('Id'));
        $grid->column('username', '用户名');
        $grid->column('domain', '域名')->link();
        $grid->column('join_group_link', '群聊跳转链接');
        $grid->column('join_group_str', '加入群聊');
        $grid->column('h5_link', 'H5链接');


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
        $show = new Show(AgentConf::findOrFail($id));



        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AdminUser());

        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->text('agentconf.domain', '域名')->required()->help('多个用|号分隔');
        $form->text('agentconf.join_group_link', '加群跳转链接')->required();
        $form->text('agentconf.join_group_str', '加入群聊')->required();
        $form->text('agentconf.h5_link', 'H5链接')->required();


        return $form;
    }
}
