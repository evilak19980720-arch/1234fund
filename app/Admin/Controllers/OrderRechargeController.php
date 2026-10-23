<?php

namespace App\Admin\Controllers;

use App\Models\Account;
use App\Models\Country;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserRechargeAddress;
use App\Models\Vip;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\Database\Eloquent\Collection;

class OrderRechargeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '充值订单';


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order());
        $data = \request()->all();
        //$query = $grid->model()->select('orders.*','users.phone','users.country')->with('payment')
        $query = $grid->model()->with('payment_third_code_recharge')->with('user');
        //->leftjoin('users','users.id','orders.user_id');
        //->where('users.admin_user_id',Admin::user()->isRole('agents') ? Admin::user()->id : 0);

        if (!empty($data) && isset($data['__search__'])) {
            $query->where('type', 1);
        } elseif (Admin::user()->isRole('servers')) {
            // 3 是为了不让查到 0和1
            $query->where('type', 3);
        } else {
            $query->where('type', 1);
        }
        $query->orderBy('orders.created_at', 'desc')->paginate();

        $grid->model()->collection(function (Collection $collection) {
            foreach($collection as $index => $item) {
                if($item->state == 1){
                    $item->recharge_num = Order::where('state',1)->where('type',1)->where('id','<=',$item->id)->where('user_id',$item->user_id)->count();
                }
            }
            return $collection;
        });

        if (Admin::user()->isRole('servers')) {
            $grid->disableActions();
            $grid->disableFilter();
        }
        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
//        $grid->selector(function (Grid\Tools\Selector $selector) {
//            $selector->select('state', '状态筛选', [
//                '' => '全部',
//                0 => '进行中',
//                1 => '成功',
//                2 => '失败',
//            ]);
//        });
        /*        $grid->quickSearch(function ($model, $query) {
                    $model->where(function ($q) use($query) {
                        $q->where('no', $query)
                            ->orWhere('user.phone', $query);
                    });
                })->placeholder('输入订单号或用户名');*/

        $grid->quickSearch(function ($model, $query) {
            $model->leftJoin('users', 'orders.user_id', 'users.id')
                ->where(function ($q) use($query) {
                    $q->where('orders.no', $query)
                        ->orWhere('users.phone', $query);
                })
                ->select('orders.*');
        })->placeholder('输入订单号或用户名');

//        $amount1 = \request("amount['start']") ? \request("amount['start']") * 100 : '';
//        $amount2 = \request("amount['end']") ? \request("amount['end']") * 100 : '';
        $grid->filter(function ($filter) {
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('payment_no', '交易单号');
                $filter->like('user.phone', '账号');
                $filter->between('amount', '金额');
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('state', '状态')->select([
                    Order::PENDING => '进行中',
                    Order::SUCCESS => '成功',
                    Order::FAILED  => '失败',
                ]);
                $filter->equal('payment_third_code_recharge.payment_third_pay_id', '支付通道')->select(Payment::query()->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)->pluck('title','id'));
                $filter->between('created_at', '创建时间')->datetime();
            });
            $filter->disableIdFilter();


//            $filter->between('amount', '金额', function ($query) use ($amount1, $amount2) {
//                $query->whereBetween('amount', [$amount1, $amount2]);
//            });
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });

        $grid->column('id', __('Id'));
        $countries = Country::getCountries();
        $countriesdata = [];
        foreach ($countries as $item){
            $countriesdata[$item->country_i] = $item->country_name;
        }
        $grid->column('user.phone', '用户')->display(function ($var)use($countriesdata){
            if(!empty($this->user->country) && isset($countriesdata[$this->user->country])){
                return $var . "({$countriesdata[$this->user->country]})";
            }else{
                return $var;
            }

        });
        $grid->column('amount', '金额')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('actual_amount', '充值货币')->display(function ($var) {
            if(isset($this->payment_third_code_recharge)){
                return $var / 100 ." ". $this->payment_third_code_recharge->payment->currery;
            }else{
                $data['trc20'] = 'USDT-TRC';
                $data['TRX'] = 'USDT-TRC';
                return $var / 100 ." (".UserRechargeAddress::getRechargeAddress($this->user_id, $this->platform) .")";
            }
        });
        $grid->column('no', '订单号');
        $grid->column('platform', '支付平台')->display(function ($var){
            if($this->payment_id != 0 && !empty($this->payment_third_code_recharge->payment)){
                return $this->payment_third_code_recharge->payment->title;
            }else{
                return $var;
            }

        });
        $grid->column('payment_no', '交易单号');
        $grid->column('state', '状态')->using([
            Order::PENDING => '进行中',
            Order::SUCCESS => '成功',
            Order::FAILED => '失败',
        ], '未知')->dot([
            Order::PENDING => 'warning',
            Order::SUCCESS => 'success',
            Order::FAILED => 'danger',
        ], 'warning');

        $grid->column('recharge_num', '充值次数');
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '更新时间');

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected
    function detail($id)
    {
        $show = new Show(Order::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('amount', __('Amount'));
        $show->field('no', __('No'));
        $show->field('payment_no', __('Payment no'));
        $show->field('paid_at', __('Paid at'));
        $show->field('closed', __('Closed'));
        $show->field('state', __('State'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected
    function form()
    {
        $form = new Form(new Order());
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

        if ($form->isEditing()) {
            $form->editing(function (Form $form) {
                $form->model()->amount = $form->model()->amount / 100;
                $form->disableReset();
                //$form->disableSubmit();
            });
        }

        // 展示区
        $form->column(1 / 2, function ($form) {

            $form->text('user.name', '用户')->disable();
            $form->currency('amount', '金额')->symbol('$');
            $form->text('no', '订单号')->disable();
            $form->text('payment_no', '交易单号')->required();
        });

        // 编辑区
        $form->column(1 / 2, function ($form) {
            $form->radio('state', '状态')->options([
                '1' => '成功',
                '2' => '失败'
            ])->required();
            $form->text('remark', '备注');
            $form->textarea('param', '请求入参')->disable();
            $form->textarea('response', '同步响应')->disable();
            $form->textarea('notify_response', '异步响应')->disable();
        });

        $form->saving(function (Form $form) {
            if ($form->state == Order::SUCCESS) {
                Order::where('no', '=', $form->model()->no)->update([
                    'amount' => $form->amount * 100,
                    'remark' => $form->remark,
                    'payment_no' => $form->payment_no,
                ]);

                $paid_at = \Carbon\Carbon::now()->timestamp;
                (new Order())->rechargeComplete(1, $form->model()->no, $form->payment_no, $paid_at, json_encode([], JSON_UNESCAPED_UNICODE));
                $success = new MessageBag([
                    'title'   => '提示',
                    'message' => '编辑成功',
                ]);

                return redirect('/admin/orders-recharge');
            }
        });

        return $form;
    }
}
