<?php

namespace App\Admin\Controllers;


use App\Admin\Actions\Pet\Altas;
use App\Events\EditPetEvent;
use App\Models\Pet;
use App\Models\PetOrder;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;

class PetController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '宠物';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Pet());
        $grid->model()
            ->withCount('petOrderBuy')
            ->where('pets.admin_user_id', '=', Admin::user()->isRole('agents') ? Admin::user()->id : 0)
            ->orderBy('sort', 'asc');
        $grid->column('id', __('Id'));
        $grid->column('name', __('宠物名称'));
//        $grid->column('image', __('图片'))->image('', 100, 50);
        $grid->column('level', __('等级'));
        $grid->column('sort', __('排序'));
        $grid->column('buying_price', __('买入价格'))->display(function ($var) {
            return $var / 100;
        });
        if(config('app.revenue_model') == 'day'){
            $grid->column('selling_price', __('每日利润'))->display(function ($var) {
                return $var / 100;
            });
        }else{
            $grid->column('selling_price', __('卖出价格'))->display(function ($var) {
                return $var / 100;
            });
            $grid->column('rate_of_return', __('回报率'))->display(function ($var) {
                return $var . '%';
            });
        }
        /*
        $grid->column('profit', __('回报'))->display(function ($var) {
            return $var / 100;
        });
        */
        $grid->column('day', __('回报周期(天)'));
        /*
        $grid->column('day_rate', __('天利率'))->display(function ($var) {
            return $var . '%';
        });
        */


        $grid->column('stock_quantity', __('库存数量'));
        $grid->column('pet_order_buy_count', __('持有量'));
        //$grid->column('automatic_quantity', __('自动合成数量'));
        /*
        $grid->column('all_count', __('总数量'))->display(function () {
            $user_holding_quantity = PetOrder::where(['pet_id' => $this->id, 'type' => 1])->count();
            return $user_holding_quantity + $this->stock_quantity + $this->waiting_quantity;
        });
        */
        $grid->column('holding_quantity', __('每个用户可持有'));//每个用户可以持有多少个
        /*
        $grid->column('rental_type', '租贸类型')->display(function ($var) {
            $arr = trans('validation.rental_type');
            return $arr[$var];
        });
        */
        $grid->column('created_at', __('创建日期'));
        $grid->column('updated_at', __('更新日期'));
        $grid->column('state', __('状态'))->display(function ($var) {
            return ['禁用', '开启'][$var];
        });
//        $grid->disableBatchActions();
        $grid->disableRowSelector();
        $grid->actions(function ($actions) {
            $actions->disableView();
            //$actions->disableDelete();
        });
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('id', '宠物')->select($this->selectPet());
        });

        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Pet());
        $form->editing(function (Form $form) {
            $form->model()->buying_price = $form->model()->buying_price / 100;
            $form->model()->selling_price = $form->model()->selling_price / 100;
            $form->model()->profit = $form->model()->profit / 100;
        });
        $form->column(1 / 2, function ($form) {
            $form->text('name', __('名称'))->rules(['required']);
            //$form->image('image', __('图片'))->rules(['required']);
            //$form->number('level', __('等级'))->default(1);
            /*
            $form->number('level', __('等级'))
                ->creationRules(['required', "unique:pets,level"])
                ->updateRules(['required', "unique:pets,level,{{id}}"]);
            */
            $form->text('sort', __('排序'))->rules(['required']);
            $form->text('buying_price', '买入价格')->rules(['required']);//currency symbol('₹')

            if(config('app.revenue_model') == 'day'){
                $form->text('selling_price', __('每日利润'))->rules(['required']);
            }else{
                $form->text('selling_price', __('卖出价格'))->rules(['required']);
                $form->text('rate_of_return', __('回报率'))->default(0);
            }

            $form->text('profit', __('回报'))->rules(['required']);
            $form->text('day', __('回报周期(天)'))->rules(['required']);
            //$form->text('day_rate', __('天利率'))->default(0);

            $form->text('holding_quantity', __('每个用户可持有'))->required();
            $form->text('stock_quantity', __('库存数量'))->required();
//          $form->select('next_pet_id', '卖出所需持有宠物')->default(0)->options($this->pets())->required();
            $form->switch('state', __('是否开启'))->default(1);

            if (Admin::user()->isRole('administrator')) {
                $form->hidden('admin_user_id')->value(0);
            }
            if (Admin::user()->isRole('agents')) {
                $form->hidden('admin_user_id')->value(Admin::user()->id);
            }

        });

        //$form->column(1 / 2, function ($form) {
        //$form->UEditor('describe', __('收益描述'))->required();
        //$form->UEditor('introduction', __('鱼描述'))->required();
        //$form->hidden('rental_type', '租贸类型')->default(Pet::JOINT_RENTAL);
        //});
        $form->saving(function ($form) {

            if ($form->buying_price >= Pet::ENTIRE_RENTAL_PRICE) {
                $form->rental_type = Pet::ENTIRE_RENTAL;
            }
            //$form->rental_type = $form->buying_price >= Pet::ENTIRE_RENTAL_PRICE ? Pet::ENTIRE_RENTAL : Pet::JOINT_RENTAL;
            $form->buying_price *= 100;
            $form->selling_price *= 100;
            $form->profit *= 100;
            //if ($form->day_rate == '') {
            //    $form->day_rate = 0;
            //}
            if ($form->rate_of_return == '') {
                $form->rate_of_return = 0;
            }
            if ($form->stock_quantity == '') {
                $form->stock_quantity = 0;
            }
        });
        //保存后回调
        $form->saved(function (Form $form) {
            //处理预约订单转购买的
            //event(new EditPetEvent($form->model()));
//            //查询预约订单
//            $pet_orders = PetOrder::where(['pet_id' => $form->model()->id, 'type' => 2, 'cancelled' => 0])->orderBy('id')->get(['id', 'pet_id']);
//            $count = $form->model()->stock_quantity;
//            if ($pet_orders->isNotEmpty() && $count> 0) {
//                foreach ($pet_orders as $item) {
//                    PetOrder::where('id', $item->id)->update(['type' => 1, 'start_at' => date('Y-m-d')]);
//                    Pet::where('id', $item->pet_id)->decrement('stock_quantity', 1);
//                    $count -= 1;
//                    if ($count <= 0) break;
//                }
//            }
        });
        $form->tools(function ($tools) {
            $tools->disableView();
            $tools->disableDelete();
        });
        return $form;
    }

    public function pets()
    {
        if (Admin::user()->isRole('agents')) {
            $pets = Pet::where('state', '=', 1)->where('pets.admin_user_id', '=', Admin::user()->id)->get();
        } else {
            $pets = Pet::where('state', '=', 1)->where('pets.admin_user_id', '=', 0)->get();
        }

        $arr[0] = '不限制';
        foreach ($pets as $pet) {
            $arr[$pet->id] = $pet->name;
        }
        return $arr;
    }

    public function getPets()
    {
        if (Admin::user()->isRole('agents')) {
            return Pet::where('state', '=', 1)->select('id', 'name as text')->where('pets.admin_user_id', '=', Admin::user()->id)->get();
        } else {
            return Pet::where('state', '=', 1)->select('id', 'name as text')->where('pets.admin_user_id', '=', 0)->get();
        }

    }

    public function selectPet()
    {
        $arr = [];
        if (Admin::user()->isRole('agents')) {
            $pets = Pet::where('state', '=', 1)->where('pets.admin_user_id', '=', Admin::user()->id)->get();
        } else {
            $pets = Pet::where('state', '=', 1)->where('pets.admin_user_id', '=', 0)->get();
        }
        foreach ($pets as $pet) {
            $arr[$pet->id] = $pet->name;
        }
        return $arr;
    }

    public function selectApi()
    {
        if (Admin::user()->isRole('agents')) {
            $data = Pet::where('state', '=', 1)->select('id', 'name as text')->where('pets.admin_user_id', '=', Admin::user()->id)->get();
        } else {
            $data = Pet::where('state', '=', 1)->select('id', 'name as text')->where('pets.admin_user_id', '=', 0)->get();
        }
        return response()->json($data);
    }

}
