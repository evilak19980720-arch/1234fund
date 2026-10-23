<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\RechargeAddress\Balance;
use App\Admin\Actions\RechargeAddress\BalanceAll;
use App\Admin\Actions\RechargeAddress\Collect;
use App\Models\Account;
use App\Models\UserRechargeAddress;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RechargeAddressController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '充值地址';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UserRechargeAddress());
        $grid->model()
            ->with('user')
            ->orderBy('balance', 'desc')->paginate();
        // 禁用CheckBox
        //$grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        // 禁用筛选
        //$grid->disableFilter();

        //禁用创建按钮
        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->batchActions(function ($batch) {
            $batch->add(new Balance());
            $batch->add(new BalanceAll());
            $batch->add(new Collect());
        });
        //$grid->quickSearch('address','user.name')->placeholder('输入TRC地址');

        $grid->quickSearch(function ($model, $query) {
            $model->leftJoin('users', 'user_recharge_addresses.user_id', 'users.id')
                ->where(function ($q) use($query) {
                    $q->where('user_recharge_addresses.address', $query)
                        ->orWhere('users.name', $query);
                })
                ->select('user_recharge_addresses.*', 'users.name');
        })->placeholder('输入TRC地址');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('address', '地址');
                $filter->like('user.name', '账号');
                $filter->between('trx_balance', 'TRX余额');
                $filter->equal('block','链')->select(['TRC'=>'TRC','BSC'=>'BSC']);

            });

            $filter->column(1 / 2, function ($filter) {
                $filter->between('balance', 'U余额');
                $filter->between('transferout_balance', 'U转出金额');
                $filter->between('transferout_trx_balance', 'TRX转出金额');
            });


        });


        $grid->column('id', __('Id'));
        $grid->column('user.name', '账号');
        $grid->column('address', '地址');
        $grid->column('balance', 'U余额')->sortable();
        $grid->column('transferout_balance', 'U转出金额')->sortable();
        $grid->column('trx_balance', 'TRX余额')->sortable();
        $grid->column('transferout_trx_balance', 'TRX转出金额')->sortable();

        $grid->header(function ($query) {

            $number = DB::table('orders')
                    ->where(function ($query){
                        $query->where('platform','=','trc20')->orWhere('platform','=','USDT-TRC');
                    })
                    ->where('type','=',Account::RECHARGE)
                    ->where('state','=',1)
                    ->sum('amount') / 100;

            $total_balance = UserRechargeAddress::query()->sum('balance');
            $total_transferout_balance = UserRechargeAddress::query()->sum('transferout_balance');
            $total_trx_balance = UserRechargeAddress::query()->sum('trx_balance');
            $total_transferout_trx_balance = UserRechargeAddress::query()->sum('transferout_trx_balance');

            return "余额合计:<b>$total_balance</b>  转出合计:<b>$total_transferout_balance</b> TRX合计:<b>$total_trx_balance</b>  TRX转出合计:<b>$total_transferout_trx_balance</b>";
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
        $form = new Form(new Card());
        $form->model()->with('user');
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
        $form->text('user.name', '账号')->readonly();
        $form->select('bank_id', '银行')->options($this->bankList())->required();
        $form->text('number', '卡号')->required();
        $form->text('ifsc', 'ifsc')->required();
        $form->text('username', '真实姓名')->required();
        $form->text('phone', '手机号')->required();
        $form->text('email', '邮箱')->required();

        return $form;
    }

    public function bankList()
    {
        $bank = Bank::all()->toArray();
        $bank_list = [];
        foreach ($bank as $k => $v) {
            $bank_list[$v['id']] = $v['name'];
        }
        return $bank_list;
    }

}
