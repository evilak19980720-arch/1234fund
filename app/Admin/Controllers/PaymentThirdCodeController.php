<?php

namespace App\Admin\Controllers;

use App\Models\Currency;
use App\Models\PaymentThirdCode;
use App\Models\Payment;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Displayers\Actions;
use Encore\Admin\Show;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Support\Facades\DB;

class PaymentThirdCodeController extends AdminController
{

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '支付通道';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PaymentThirdCode());

        $query = $grid->model()->with(['payment'])->orderBy('sort')->paginate();

        $grid->id(__('Id'));


        $grid->column('payment.title', '支付平台');
        $grid->title('通道名称');
        $grid->pay_type('通道类型');
        $grid->pay_code('通道编码');
        $grid->is_open('状态')->bool();
        $grid->column('limit', '充值限额')->display(function () {
            return sprintf('%d - %d', $this->upper / 100, $this->lower / 100);
        });

        $grid->column('sort', '排序');

        $grid->disableExport();
        $grid->setActionClass(Actions::class);
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableFilter();

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
        $form = new Form(new PaymentThirdCode());
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

        $form->select('payment_third_pay_id', '平台名称')->options($this->payments())->required();
        $form->text('title', '通道名称')->required();
        $form->switch('is_open', '状态')->default(1);
        $form->text('pay_type', '通道类型')->required();
        $form->text('pay_code', '通道编码')->required();

        $form->number('sort', '排序')->min(0)->default(0);

        $form->currency('upper', '充值上限')->symbol('₹')->required();
        $form->currency('lower', '充值下限')->symbol('₹')->required();
        $form->text('remark', '备注');



        $form->editing(function (Form $form) {
            $form->model()->upper = $form->model()->upper / 100;
            $form->model()->lower = $form->model()->lower / 100;
        });
        $form->saving(function ($form) {
            $form->upper = $form->upper * 100;
            $form->lower = $form->lower * 100;
        });

        return $form;
    }


    public function payments(): array
    {
        $hierarchies = Payment::all()->toArray();
        $data = [];
        foreach ($hierarchies as $k => $v) {
            $data[$v['id']] = $v['title'];
        }
        return $data;
    }

}
