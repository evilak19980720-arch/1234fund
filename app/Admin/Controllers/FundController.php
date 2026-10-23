<?php

namespace App\Admin\Controllers;

use App\Models\Crowd;
use App\Models\Fund;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

class FundController extends AdminController
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
        $grid = new Grid(new Fund());
        $grid->model()->with('pet')
            ->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)
            ->orderBy('id', 'desc');
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

        $grid->disableFilter();
        $grid->disableRowSelector();
        $grid->column('id', __('Id'));
        $grid->column('pet.name', '宠物');
        $grid->column('time','排期时间段')->display(function () {
            return sprintf('%s - %s',$this->start,$this->end);
        });
        $grid->column('interval', '间隔(分)');
        $grid->column('state', '状态')->bool();
        $grid->column('sort', '排序');
        $grid->column('rate', '转让费率');
        return $grid;
    }

    /**
     * @param $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Fund::query()->find($id));

        $show->field('id', __('Id'));
        $show->field('pet_id', __('Pet id'));
        $show->field('start', __('Start'));
        $show->field('end', __('End'));
        $show->field('interval', __('Interval'));
        $show->field('state', __('State'));
        $show->field('sort', __('Sort'));
        $show->field('rate', __('Rate'));
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
        $form = new Form(new Fund());
        $form->tools(function (Form\Tools $tools) {
            // 去掉`查看`按钮
            $tools->disableView();
            // 去掉`删除`按钮
            $tools->disableDelete();
        });

        $form->setWidth(2, 2);
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
        $form->select('pet_id', '宠物')->options('/admin/api/pets')->required();
        $form->timeRange('start','end', '排期时间端')->required();
        $form->number('interval','间隔')->default(5)->min(2)->required();
        $form->switch('state', '开关');
        $form->number('sort', '排序')->default(0)->required();
        $form->rate('rate', '转让费率')->required();
        $form->textarea('desc', '描述')->required();
        if (Admin::user()->isRole('administrator')) {
            $form->hidden('admin_user_id')->value(0);
        }
        if (Admin::user()->isRole('agents')) {
            $form->hidden('admin_user_id')->value(Admin::user()->id);
        }

        //创建的时候才进行排期生成
        if ($form->isCreating()) {
            $form->saved(function (Form $form) {
                Artisan::call('period:generate');
            });
        }
        return $form;
    }
}
