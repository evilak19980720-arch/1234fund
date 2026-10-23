<?php

namespace App\Admin\Controllers;

use App\Models\Pet;
use App\Models\PetOrder;
use App\Models\PetQuicken;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PetQuickenController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '加速记录';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PetOrder());
        $query = $grid->model()
            ->join('users','pet_orders.user_id','users.id')
            ->where('users.admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0);
        if (Admin::user()->isRole('servers')) {
            // 3 是为了不让查到 0和1
            $query->where('id','<',0);
        }
        $query->orderBy('id', 'desc')->paginate();
        $grid->disableExport();
        if (Admin::user()->isRole('servers')) {
            $grid->disableActions();
            $grid->disableFilter();
        }

        $grid->column('id', __('Id'));
        $grid->column('user.name', __('用户名'));
        $grid->column('pet.name', __('宠物'));
        $grid->column('price', __('单价'))->display(function ($var) {
            return $var / 100;
        });
        $grid->column('number', __('个数'));
        $grid->column('amount', __('金额'))->display(function ($var) {
            return $var / 100;
        });
        $grid->column('type', __('类型'))->display(function ($var) {
            $arr = [1 => '买入', 2 => '预约', 3 => '卖出'];
            return $arr[$var];
        });
        $grid->column('source', __('来源'))->display(function ($var) {
            if($this->type == 1){
                $arr = [0 => '买入', 1 => '众筹'];
                return $arr[$var];
            }else{
                return '';
            }

        });
        $grid->column('created_at', __('创建时间'));
        $grid->column('updated_at', __('更新时间'));
        $grid->disableCreateButton();

        $grid->quickSearch(function ($model, $query) {
            $model->join('users', 'pet_orders.user_id', 'users.id')
                ->Where('users.name', $query)
                ->select('pet_orders.*', 'users.name');
        })->placeholder('输入用户名');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('user.name', '用户名');
            $filter->between('created_at', '创建时间')->datetime();
            $filter->equal('pet_id', '宠物')->select($this->selectPet());
            $filter->between('amount', '金额');
            $filter->equal('type', '类型')->select([
                1 => '买入',
                2 => '预约',
                3 => '卖出',
            ]);
        });
        $grid->disableActions();
        $grid->disableRowSelector();
        return $grid;
    }

    public function selectPet()
    {
        $arr = [];
        $pets = Pet::get(['id', 'name']);
        foreach ($pets as $pet) {
            $arr[$pet->id] = $pet->name;
        }
        return $arr;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(PetOrder::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('pet_id', __('Pet id'));
        $show->field('amount', __('Amount'));
        $show->field('type', __('Type'));
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
        $form = new Form(new PetOrder());

        $form->number('user_id', __('User id'));
        $form->number('pet_id', __('Pet id'));
        $form->number('amount', __('Amount'));
        $form->switch('type', __('Type'));

        return $form;
    }
}
