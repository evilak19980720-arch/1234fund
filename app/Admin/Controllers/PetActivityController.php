<?php

namespace App\Admin\Controllers;

use App\Models\PetActivity;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PetActivityController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '宠物活动';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PetActivity());
        $grid->model()
            ->select('pet_activities.*')->orderBy('weigh', 'desc')
            ->join('pets','pets.id','pet_activities.pet_id')
            ->where('pets.admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0);
        $grid->column('id', __('Id'));
        $grid->column('start_at', __('开始时间'));
        $grid->column('end_at', __('结束时间'));
//        $grid->column('pet_id', __('宠物ID'));
        $grid->column('pet.name', __('宠物名'));
        $grid->column('act_buying_price', __('活动购买价格'))->display(function ($var) {
            return $var / 100;
        });
        if(config('app.revenue_model') == 'day') {
            $grid->column('act_selling_price', __('每日收益'))->display(function ($var) {
                return $var / 100;
            });
        }else{
            $grid->column('act_selling_price', __('活动卖出价格'))->display(function ($var) {
                return $var / 100;
            });
            $grid->column('act_rate_of_return', __('回报率'));
        }



        $grid->column('act_day', __('回报周期(天)'));
        $grid->column('max_stock_quantity', __('最大总库存'));
        $grid->column('created_at', __('创建日期'));
        $grid->column('updated_at', __('更新日期'));
//        $grid->column('deleted_at', __('Deleted at'));
        $grid->column('weigh', __('权重'));
        $grid->column('state', __('状态'))->display(function ($var) {
            return ['禁用', '开启'][$var];
        });

        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
        });

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
        $show = new Show(PetActivity::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('start_at', __('Start at'));
        $show->field('end_at', __('End at'));
//        $show->field('pet_id', __('Pet id'));
        $show->field('pet.name', __('宠物名'));
        $show->field('act_buying_price', __('Act buying price'));
        $show->field('act_selling_price', __('Act selling price'));
        $show->field('act_profit', __('Act profit'));
        $show->field('act_day', __('Act day'));
        $show->field('max_stock_quantity', __('Max stock quantity'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
//        $show->field('deleted_at', __('Deleted at'));
        $show->field('weigh', __('Weigh'));
        $show->field('state', __('State'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new PetActivity());
        $form->editing(function (Form $form) {
            $form->model()->act_buying_price = $form->model()->act_buying_price / 100;
            $form->model()->act_selling_price = $form->model()->act_selling_price / 100;
            $form->model()->act_profit = $form->model()->act_profit / 100;
        });
        $form->select('pet_id', __('选择宠物'))->options('/admin/pet/selectApi')->rules('required');
        $form->datetime('start_at',__('Start at'))->rules('required');
        $form->datetime('end_at', __('End at'))->rules('required');
        $form->number('act_buying_price', __('Act buying price'))->rules('required');

        if(config('app.revenue_model') == 'day') {
            $form->number('act_selling_price', __('每日收益'))->rules('required');
        }else{
            $form->number('act_selling_price', __('Act selling price'))->rules('required');
            $form->number('act_rate_of_return', __('回报率'))->rules('required');
        }



        //$form->number('act_selling_price', __('Act selling price'))->rules('required');

        $form->number('act_profit', __('Act profit'))->rules('required');
        $form->number('act_day', __('Act day'))->rules('required');
        $form->number('max_stock_quantity', __('Max stock quantity'))->rules('required');
        $form->number('weigh', __('Weigh'))->rules('required');
        $form->switch('state', __('State'));
        $form->saving(function ($form) {
            $form->act_buying_price *= 100;
            $form->act_selling_price *= 100;
            $form->act_profit *= 100;
        });
        return $form;
    }
}
