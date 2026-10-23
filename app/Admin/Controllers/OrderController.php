<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Order\Audit;
use App\Admin\Actions\Order\Log;
use App\Admin\Actions\Order\Retry;
use App\Jobs\PayOrder;
use App\Models\Account;
use App\Models\Country;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Payment;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Widgets\Tab;

class OrderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '提现订单';

    /*
     *
     * use Encore\Admin\Widgets\Tab;

$tab = new Tab();

$tab->add('Pie', $pie);
$tab->add('Table', new Table());
$tab->add('Text', 'blablablabla....');

echo $tab->render();
     * */

    public function __construct()
    {
        $this->get_currery = request()->get_currery;
        if(empty($this->get_currery)){
            $this->get_currery = 'ALL';
        }
        return $this;
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order());

        $data = \request()->all();
        $query = $grid->model()->with('user');

        if (!empty($data) && isset($data['__search__'])) {
            $query->where('type', 0);
        } elseif (Admin::user()->isRole('servers')) {
            // 3 是为了不让查到 0和1
            $query->where('type', 3);
        } else {
            $query->where('type', 0);
        }
        $query->orderBy('orders.id', 'desc')->paginate();

        $grid->header(function ($query) {
            $tab = new Tab();
            $tab->addLink('全部', '?get_currery=ALL',$this->get_currery=='ALL' ? true : false);
            $tab->addLink('USDT-TRC', '?get_currery=USDT-TRC',$this->get_currery=='USDT-TRC' ? true : false);
            $tab->addLink('USDT-BSC', '?get_currery=USDT-BSC',$this->get_currery=='USDT-BSC' ? true : false);
            $tab->addLink('TRX', '?get_currery=TRX',$this->get_currery=='TRX' ? true : false);
            $tab->addLink('BDT', '?get_currery=BDT',$this->get_currery=='BDT' ? true : false);
            $tab->addLink('PHP', '?get_currery=PHP',$this->get_currery=='PHP' ? true : false);
            $tab->addLink('INR', '?get_currery=INR',$this->get_currery=='INR' ? true : false);
            $tab->addLink('EGP', '?get_currery=EGP',$this->get_currery=='EGP' ? true : false);
            $tab->addLink('VND', '?get_currery=VND',$this->get_currery=='VND' ? true : false);
            return $tab->render();
        });

        if($this->get_currery != 'ALL'){
            $query->where('currery','=',$this->get_currery);
        }


        if (Admin::user()->isRole('servers')) {
            $grid->disableActions();
            $grid->disableFilter();
        }
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
        $grid->quickSearch(function ($model, $query) {
            $model->leftJoin('users', 'orders.user_id', 'users.id')
                ->where(function ($q) use($query) {
                    $q->where('orders.no', $query)
                        ->orWhere('users.name', $query);
                })
                ->select('orders.*', 'users.name');
        })->placeholder('输入订单号或用户名');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('payment_no', '交易单号');
                $filter->like('user.name', '账号');
                $filter->like('user.phone', '手机');
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('state', '状态')->select([
                        Order::REVIEW  => '待审核',
                        Order::PENDING  => '进行中',
                        Order::SUCCESS  => '成功',
                        Order::FAILED  => '失败',
                        Order::CANCEL  => '取消',
                        Order::REFUSE  => '拒绝'
                ]);
                $filter->equal('payment_id', '支付通道')->select(Payment::query()->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)->pluck('title','id'));
                $filter->between('created_at', '创建时间')->datetime();
            });


        });

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            //$actions->add(new Retry());
        });
        $grid->batchActions(function ($batch) {
            $batch->add(new Audit());
        });

        $grid->column('id', __('Id'));
        $countries = Country::getCountries();
        $countriesdata = [];
        foreach ($countries as $item){
            $countriesdata[$item->country_i] = $item->country_name;
        }
        $grid->column('user.name', '用户')->display(function ($var)use($countriesdata){
            $str = "";
            if(!empty($this->user->ip_register)){
                $ip_count = User::where('ip_register','=',$this->user->ip_register)->count();
                //$ip_count = 1;
                //
                $str =  $var.'(<a href="/admin/users?&ip_register='.$this->user->ip_register.'" target="_blank">同IP:'.$ip_count.'</a>)';
            }else{
                $str =  $var;
            }
            if(!empty($this->user->country) && isset($countriesdata[$this->user->country])){
                $str .=  "[{$countriesdata[$this->user->country]}]";
            }

            return $str;
        });
        $grid->column('amount', '金额')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('handlingfee', '手续费')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('actual_amount', '实际到账')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('currery_amount', '货币金额')->display(function ($var) {
            return $this->currery ." ". $var / 100;
        });

        $grid->column('no', '订单号');
        $grid->column('platform', '支付平台');
        $grid->column('payment_no', '交易单号')->width(150);
/*        $grid->column('closed', '关闭')->display(function ($var) {
            return $var ? '<span class="text-success fa fa-check"><span>' : '<span class="text-danger fa fa-close"><span>';
        });*/
        //拒绝蓝色  失败红色 进行中黄色  成功绿色
        //`danger`、`warning`、`info`、`primary`、`default`、`success`
        $grid->column('state', '状态')->using([
            Order::REVIEW => '审核中',
            Order::PENDING => '进行中',
            Order::SUCCESS => '成功',
            Order::FAILED => '失败',
            Order::CANCEL => '取消',
            Order::REFUSE => '拒绝',
        ], '未知')->dot([
            Order::REVIEW => 'primary',
            Order::PENDING => 'warning',
            Order::SUCCESS => 'success',
            Order::FAILED => 'danger',
            Order::CANCEL => 'default',
            Order::REFUSE => 'info',
        ], 'warning');
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
    protected function detail($id)
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
    protected function form()
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

                //屏蔽提现详情页面编辑
                $form->disableReset();
                $form->disableSubmit();

                // 已经人工操作过
                /*
                if ($form->model()->closed || in_array($form->model()->state, [Order::PENDING, Order::SUCCESS, Order::CANCEL, Order::REFUSE])) {
                    $form->disableReset();
                    $form->disableSubmit();
                }
                */
            });
        }

        $form->column(1 / 2, function ($form) {
            $form->text('user.name', '用户')->disable();
            $form->currency('amount', '金额')->symbol('₹')->disable();
            $form->text('no', '订单号')->disable();
            $form->text('payment_no', '平台订单号')->disable();
            $form->radio('closed', '订单关闭')->options([
                '0' => '未关闭',
                '1' => '已关闭'
            ])->disable();
            $form->radio('type', '类型')->options([
                '0' => '提现',
                '1' => '充值'
            ])->disable();
        });

        $form->column(1 / 2, function ($form) {
            $form->radio('state', '状态')->options([
                Order::PENDING => '通过',
                Order::REFUSE => '拒绝',
                Order::CANCEL => '取消',
            ]);
            $form->text('remark', '备注');
            $form->textarea('card', '收款账号')->disable();
            $form->textarea('param', '请求入参')->disable();
            $form->textarea('response', '同步响应')->disable();
            $form->textarea('notify_response', '异步响应')->disable();
        });

        $form->saved(function (Form $form) {
            // 记录操作
            $form->model()->logs()->create([
                'admin_id' => Admin::user()->id,
                'order_id' => $form->model()->id,
                'no' => $form->model()->no,
                'payment_no' => $form->model()->payment_no,
                'remark' => $form->model()->remark,
                'error' => $form->model()->operation,
                'paid_at' => $form->model()->paid_at
            ]);

            if ($form->model()->state == Order::PENDING) {  // 通过
                if (!$form->model()->platform) {
                    $payment = Payment::where('status', '=', 1)->first();
                    if ($payment) {
                        $form->model()->platform = $payment->name;
                        $form->model()->platform_id = $payment->payment_id;
                    }
                }

                if ($form->model()->payment_no) {
                    // 变更当前订单
                    $form->model()->no = only_code();
                    $form->model()->payment_no = '';
                    $form->model()->paid_at = '';
                    $form->model()->closed = 0;
                    $form->model()->state = $form->model()->platform ? Order::PENDING : Order::FAILED;
                    $form->model()->remark = $form->model()->platform ? '' : 'platform error';
                    $form->model()->operation = '';
                } else {
                    $form->model()->state = Order::PENDING;
                }
                $form->model()->save();

                // 发起新订单
                PayOrder::dispatch($form->model())->onQueue(env('REDIS_QUEUE', 'default'));
            } elseif ($form->model()->state == Order::REFUSE) { // 拒绝
                User::where('id', '=', $form->model()->user_id)->decrement('frozen', $form->model()->amount);  // 减冻结
                // 记录备注
                Order::where('id', '=', $form->model()->id)->update([
                    'closed' => 1
                ]);
            } elseif ($form->model()->state == Order::CANCEL) { // 取消
                User::where('id', '=', $form->model()->user_id)->decrement('frozen', $form->model()->amount);  // 减冻结
                User::where('id', '=', $form->model()->user_id)->increment('balance', $form->model()->amount);  // 加余额
                Account::create([
                    'order_id' => $form->model()->id,
                    'type' => Account::WITHDRAWAL_FAIL,
                    'to' => $form->model()->user_id,
                    'form' => 0,
                    'amount' => $form->model()->amount,
                    'level' => 0
                ]);

                // 总提现次数-1
                User::where('id', '=', $form->model()->user_id)->decrement('cash_out_times', 1);

                // 记录备注
                Order::where('id', '=', $form->model()->id)->update([
                    'closed' => 1
                ]);
            }

        });

        return $form;
    }
}
