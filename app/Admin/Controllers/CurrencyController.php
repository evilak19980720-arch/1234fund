<?php

namespace App\Admin\Controllers;

use App\Models\Currency;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;

class CurrencyController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '货币管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Currency());

        $query = $grid->model()->orderBy('sort');
        $grid->paginate(100);
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        $grid->disableFilter();
        // 筛选
        $grid->quickSearch('continent','country_i','country_name','area','lang');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
            //$actions->disableDelete();
        });

        $grid->column('id', __('Id'));
        $grid->column('code', '货币编码');
        $grid->column('name', '货币名称');
        $grid->column('symbol', '货币符号');
        $grid->column('exchange_rate', '汇率')->display(function($var){
            return floatval($var);
        });
        $grid->column('recharge_type', '充提方式');
        $grid->column('sort', '排序')->text();
        $grid->column('state', '充值状态')->switch([
            'on' => ['value' => 1, 'text' => '启用', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => '禁用', 'color' => 'default'],
        ]);
        $grid->column('withdrawal_state', '提现状态')->switch([
            'on' => ['value' => 1, 'text' => '启用', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => '禁用', 'color' => 'default'],
        ]);

        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Currency());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
        $form->editing(function (Form $form) {
            $form->model()->exchange_rate = floatval($form->model()->exchange_rate);
        });
        $form->text('code', '货币编码')->required();
        $form->text('name', '货币名称')->required();
        $form->text('symbol', '货币符号')->required();
        $form->text('exchange_rate', '汇率')->required();
        $form->select('recharge_type', '充值方式')->required()->options([
            'USDT' => 'USDT',
            'OTHER' => 'OTHER'
        ]);
        $form->number('sort', '排序')->required()->default(0)->min(0)->help('从小到大');
        $form->switch('state', '充值状态')->default(1);
        $form->switch('withdrawal_state', '提现状态')->default(0);


        return $form;
    }


}
