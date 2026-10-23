<?php

namespace App\Admin\Controllers;

use App\Models\Pet;
use App\Models\PetOrder;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

class PetOrderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '交易';


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PetOrder());
        $query = $grid->model()->with(['user','pet'])
            ->whereHas('user',function($query){
                $query->where('users.admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0);
            })
            ->where('type','=',1);
        if (Admin::user()->isRole('servers')) {
            // 3 是为了不让查到 0和1
            $query->where('pet_orders.id','<',0);
        }
        $query->orderBy('pet_orders.id', 'desc')->paginate();
        $grid->disableExport();
        if (Admin::user()->isRole('servers')) {
            $grid->disableActions();
            $grid->disableFilter();
        }

        $grid->column('id', __('Id'));
        $grid->column('no', __('订单号'));
        $grid->column('user.name', __('用户名'));
        $grid->column('pet.name', __('项目'));
        /*
                $grid->column('price', __('单价'))->display(function ($var) {
                    return $var / 100;
                });
        */
        //$grid->column('number', __('个数'));
        $grid->column('day', __('周期'));
        if(config('app.revenue_model') == 'day') {
            $grid->column('sell_day', __('当前收益天数'));
            $grid->column('start_at', __('开始收益'))->display(function ($var){
                if(!empty($var)){
                    return Carbon::parse($var)->format('Y-m-d');
                }else{
                    return "未开始";
                }
            });
        }
        $grid->column('amount', __('金额'))->display(function ($var) {
            return $var / 100;
        });


        if(config('app.revenue_model') == 'day') {
            $grid->column('selling_amount', __('每日收益'))->display(function ($var) {
                return $var / 100;
            });
        }else{
            $grid->column('selling_amount', __('卖出价格'))->display(function ($var) {
                return $var / 100;
            });
            $grid->column('rate_of_return', __('利率'));
            $grid->column('profit_amount', __('收益'))->display(function ($var) {
                return $var / 100;
            });
        }




        $grid->column('type', __('类型'))->display(function ($var) {
            $arr = [1 => '买入', 2 => '预约', 3 => '卖出'];
            $text = $arr[$var];
            if($var == 2 && $this->cancelled == 1){
                $text .= '[已取消]';
            }
            if ($var == 1) {
                $text .= $this->state ? '[已出售]' : '[未出售]';
            }
            return $text;
        });
        /*
        $grid->column('source', __('来源'))->display(function ($var) {
            if($this->type == 1){
                $arr = [0 => '买入', 1 => '众筹'];
                return $arr[$var];
            }else{
                return '';
            }

        });
        */
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
            $filter->like('no', '订单号');
            $filter->between('created_at', '创建时间')->datetime();
            $filter->equal('pet_id', '项目')->select($this->selectPet());
            $filter->between('amount', '金额');

            $filter->equal('state', '出售')->select([
                0 => '未出售',
                1 => '已出售'
            ]);
            /*
                        $filter->equal('type', '类型')->select([
                            1 => '买入',
                            2 => '预约',
                            3 => '卖出',
                        ]);
            */
        });
        $grid->disableActions();
        $grid->disableRowSelector();
        return $grid;
    }

    public function selectPet()
    {
        $arr = [];
        $pets = Pet::query()->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)->get(['id', 'name']);
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
