<?php

namespace App\Admin\Controllers;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Locale;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;

class CountryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '国家管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Country());

        $query = $grid->model()->orderBy('sort');
        $grid->paginate(500);
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
        $grid->column('continent', '州');
        $grid->column('country_i', '编码');
        $grid->column('country_name', '名称');
        $grid->column('area', '区号');
        $grid->column('lang', '语言');
        $grid->column('icon','图标');// ->lightbox(['width' => 25, 'height' => 25]);
        $grid->column('default_currery', '默认货币');
        $grid->column('currery', '货币');
        $grid->column('sort', '排序')->text();
        $grid->column('open_reg', '开放注册')->bool();
        $grid->column('first_withdrawal_free', '首次提现免费')->bool();
        $grid->column('withdrawal_amount', '提现限额')->display(function ($value) {
            return $value / 100;
        });
        $grid->column('state', '状态')->bool();

        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Country());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
        $form->editing(function (Form $form) {
            $form->model()->withdrawal_amount = $form->model()->withdrawal_amount / 100;
        });
        $form->text('continent', '州')->required();
        $form->text('country_i', '编码')->required();
        $form->text('country_name', '名称')->required();
        $form->text('icon', '图标')->required();
        $form->text('area', '区号')->required();
        $form->select('lang', '语言')->options(Locale::getAdminLangs())->required();
        $form->text('default_currery', '默认货币')->required();
        $form->multipleSelect('currery', '可用货币')->required()->options(call_user_func(function () {
            $data = [];
            $list = Currency::getCurrencies();
            foreach ($list as $val){
                $data[$val['code']] = $val['code'];
            }
            return $data;
        }));
        $form->number('sort', '排序')->required()->default(0)->min(0)->help('从小到大');
        $form->switch('open_reg', '开放注册')->default(1);
        $form->switch('first_withdrawal_free', '首次提现免费')->default(1);


        $form->text('withdrawal_amount', '提现限额')->default(0);


        $form->switch('state', '状态')->default(1);

        $form->saving(function ($form) {
            //$form->currery = implode(',',$form->currery);
            $form->withdrawal_amount = $form->withdrawal_amount * 100;
        });

        return $form;
    }


}
