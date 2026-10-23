<?php

namespace App\Admin\Controllers;

use App\Models\Bank;
use App\Models\Card;
use App\Models\Currency;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Cache;

class CardController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '银行卡管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Card());
        $grid->model()
            ->with('user')
            ->with('bank')
            ->with('provinces')
            ->with('citys')
            ->withTrashed()
            ->orderBy('created_at', 'desc')->paginate();
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        // 禁用筛选
        //$grid->disableFilter();

        //禁用创建按钮
        $grid->disableCreateButton();

        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->quickSearch('number')->placeholder('输入卡号');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('user.name', '用户');
            $filter->like('number', '卡号');
            $filter->like('ifsc', 'ifsc');
            $filter->like('phone', '手机号');
            $filter->between('created_at', '创建时间')->datetime();
        });

        $grid->column('id', __('Id'));
        $grid->column('currery', '货币');
        $grid->column('user.name', '账号');
        $grid->column('bank.name', '银行');
        $grid->column('number', '卡号');
        $grid->column('ifsc', 'ifsc');
        $grid->column('username', '真实姓名');
        $grid->column('phone', '手机号');
        $grid->column('email', '邮箱');
        $grid->column('created_at', '创建时间')->date('Y-m-d H:i');
        $grid->column('deleted_at', '删除时间')->date('Y-m-d H:i');
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
        $show = new Show(Bank::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));

        return $show;
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
        $form->select('currery', '货币')->options(call_user_func(function () {
            $data = [];
            $list = Currency::getCurrencies();
            foreach ($list as $val){
                $data[$val['code']] = $val['code'];
            }
            return $data;
        }));
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
