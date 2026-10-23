<?php

namespace App\Admin\Controllers;

use App\Models\Crowd;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CrowdController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '彩种';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Crowd());
        $grid->model()->select('crowds.*')->orderBy('id', 'desc')
            ->join('pets','pets.id','crowds.pet_id')
            ->where('pets.admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0);
        $grid->disableExport();
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
        });
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
        });

        $grid->column('id', __('Id'));
        $grid->column('pet.name', '宠物');
        $grid->column('portion', '份额');
        $grid->column('time','时间')->display(function () {
            return sprintf('%s - %s',$this->start,$this->end);
        });
        $grid->column('interval', '间隔(分)');
        $grid->column('state', '状态')->bool();
        $grid->column('sort', '排序');
        //$grid->column('robot', '机器人')->bool();
        //$grid->column('ratio', '比例');
        $grid->column('win', '机器人胜率');
        $grid->column('user_win', '用户中奖因数');
        $grid->column('rate', '转让费率');

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
        $show = new Show(Crowd::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('pet_id', __('Pet id'));
        $show->field('amount', __('Amount'));
        $show->field('start', __('Start'));
        $show->field('end', __('End'));
        $show->field('interval', __('Interval'));
        $show->field('state', __('State'));
        $show->field('sort', __('Sort'));
        $show->field('robot', __('Robot'));
        $show->field('ratio', __('Ratio'));
        $show->field('win', __('Win'));
        $show->field('rate', __('Rate'));
        $show->field('user_win', __('user_win'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Crowd());
        $form->setWidth(2, 2);
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->select('pet_id', '宠物')->options('/admin/api/pets')->required();
        $form->number('portion','份额')->min(1)->required();
        $form->timeRange('start','end', '开奖时间')->required();
        $form->number('interval','间隔')->default(5)->required();
        $form->switch('state', '状态');
        $form->number('sort', '排序')->required();
        //$form->switch('robot', '机器人');
        //$form->number('ratio', '购买比例')->min(10)->max(20)->required();
        $form->number('win', '机器人胜率')->min(0)->max(100)->required();
        $form->number('user_win', '用户中奖因数')->default(1)->required();
        $form->rate('rate', '转让费率')->required();
        $form->textarea('desc', '描述');

        $form->editing(function (Form $form) {
            $form->model()->amount = $form->model()->amount / 100;
        });

        $form->saving(function (Form $form) {
            $form->amount = $form->amount * 100;
        });

        return $form;
    }
}
