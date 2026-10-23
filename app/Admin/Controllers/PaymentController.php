<?php

namespace App\Admin\Controllers;

use App\Models\AdminUser;
use App\Models\Currency;
use App\Models\Hierarchy;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Vip;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Displayers\Actions;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;

class PaymentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '支付平台';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Payment());
        $query = $grid->model()->with(['admin','orderRecharge'])->paginate();
        if (Admin::user()->isRole('agents')) {
            $query ->where('admin_user_id','=',Admin::user()->id);
        }
        $grid->id(__('Id'));
        if (Admin::user()->isRole('administrator')) {
            $grid->column('admin.name', '代理');
        }
        $grid->title('名称');
        $grid->server_name('平台名称');
        $grid->merchant('商户号');
        /*
        $grid->column('pay_key','密钥')->display(function (){
            return '代收：'.$this->pay_key.'<br>代付：'.$this->withdrawal_key;
        });
        */
        //$grid->password('密码');
        $grid->column('limit', '充值限额')->display(function () {
            return sprintf('%d - %d', $this->lower / 100, $this->upper / 100);
        });
        $grid->currery('货币');


        $grid->column('sort', '排序');
        $grid->is_open('状态')->bool();
        $grid->pay('代收开关')->bool();
        $grid->withdrawal('代付开关')->bool();
        $grid->column('give', '充值赠送')->display(function ($val) {
            return $val . '%';
        });
        $grid->column('recharge', '充值')->display(function () {
            $amount = $this->orderRecharge->sum('amount');
            return $amount / 100;
        });

//        $grid->column('Withdraw', '提现')->display(function () {
//            $amount = Order::where('type', '=', 0)->where(['state' => Order::SUCCESS, 'payment_id' => $this->id])->sum('actual_amount');
//            return $amount / 100;
//        });


        if (Admin::user()->isRole('agents')) {
            $grid->disableCreateButton();
        }
        $grid->disableExport();
        $grid->setActionClass(Actions::class);
        $grid->actions(function ($actions) {
            $actions->disableView();
            if (Admin::user()->isRole('agents')) {
                $actions->disableDelete();
            }
        });

        if (!Admin::user()->isRole('agents')) {
            //平台  名称 代理
            $grid->quickSearch('title')->placeholder('输入支付平台名称');
            $grid->filter(function ($filter) {
                $filter->disableIdFilter();
                $filter->equal('admin_user_id', '代理商')->select($this->admin_user());
            });
        } else {
            $grid->disableFilter();
        }


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
        $show = new Show(Payment::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('type', __('Type'));
        $show->field('name', __('Name'));
        $show->field('merchant', __('Merchant'));
        $show->field('key', __('Key'));
        $show->field('password', __('Password'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Payment());
        $form->tools(function (Form\Tools $tools) {
            // 去掉`查看`按钮
            $tools->disableView();
            // 去掉`删除`按钮
            $tools->disableDelete();
        });
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
        $is_edit = $form->isEditing();
        if ($is_edit) {
            $form->display('server_name', '平台名称');
        } else {
            //添加
            $form->select('server_name', '平台名称')->options(Payment::PAY_OPTION)->required();
        }
        $form->text('title', '名称')->required();
        $form->text('merchant', '商户号')->required();
        $form->select('currery', '货币')->options(call_user_func(function () {
            $data = [];
            $list = Currency::getCurrencies();
            foreach ($list as $val){
                $data[$val['code']] = $val['code'];
            }
            return $data;
        }));
        $form->text('pay_key', '代收密钥')->required();
        $form->text('withdrawal_key', '代付密钥')->required();
        $form->text('password', '密码');
        $form->text('pay_url', '代收接口')->required();
        $form->text('pay_query_url', '代收查询接口')->default('');
        $form->text('pay_code', '代收通道');
        $form->text('withdrawal_url', '代付接口')->required();
        $form->text('withdrawal_query_url', '代付查询接口');
        $form->text('withdrawal_code', '代付通道');
        $form->text('public_key', '平台公钥');
        $form->text('private_key', '商户私钥');
        $form->number('sort', '排序')->min(0)->default(0);
        $form->switch('pay', '代收开关')->default(Payment::PAY_OPEN);
        $form->switch('withdrawal', '代付开关')->default(Payment::WITHDRAWAL_OPEN);
        $form->switch('is_open', '状态')->default(Payment::YES_OPEN);
        $form->currency('upper', '充值上限')->symbol('₹')->required();
        $form->currency('lower', '充值下限')->symbol('₹')->required();
        $form->rate('give', '充值赠送')->default(0)->required();
        if (Admin::user()->isRole('administrator')) {
            $form->select('admin_user_id', '代理')->options($this->admin_user())->default(0);
        }
        $form->editing(function (Form $form) {
            $form->model()->upper = $form->model()->upper / 100;
            $form->model()->lower = $form->model()->lower / 100;
        });
        $form->saving(function ($form) use ($is_edit) {
            if (!$is_edit && $form->password === null) {
                $form->password = '';
            }
            if (!$is_edit) {
                $form->pay_query_url = $form->pay_query_url ?: '';
                $form->pay_code = $form->pay_code ?: '';
                $form->withdrawal_query_url = $form->withdrawal_query_url ?: '';
                $form->withdrawal_code = $form->withdrawal_code ?: '';
            }
            $form->upper = $form->upper * 100;
            $form->lower = $form->lower * 100;
        });

        return $form;
    }


    public function admin_user()
    {
        $data = Administrator::query()->whereHas('roles', function ($query) {
            $query->where('slug', 'agents');
        })
            ->pluck('name', 'id')->toArray();
        $data[0] = '无代理';
        ksort($data);
        return $data;
    }

}
