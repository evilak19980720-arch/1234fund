<?php

namespace App\Admin\Controllers;

use App\Admin\Models\AgentStatistic;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;


class AgentStatisticController extends AdminController
{
    protected $title = '代理统计';

    protected function grid()
    {
        $grid = new Grid(new AgentStatistic());
        $grid->disableExport();
        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableActions();
        $grid->disablePagination();
        $grid->filter(function($filter){
            $filter->disableIdFilter();
        });
        $grid->column('admin_name', '用户名');

        $grid->column('today_recharge', '今日充值')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('today_withdrawal', '今日提现')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('yesterday_recharge', '昨日充值')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('yesterday_withdrawal', '昨日提现')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('recharge', '总充值')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('withdrawal', '总提现')->display(function ($var) {
            return $var / 100;
        });

        $grid->column('today_register', '今日注册');
        $grid->column('yesterday_register', '昨日注册');
        $grid->column('register', '总注册');

        return $grid;
    }
}
