<?php

namespace App\Admin\Controllers;

use App\Models\PaymentDoc;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PaymentDocController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '支付凭证';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PaymentDoc());
        $grid->model()->leftjoin('users','users.id','payment_docs.user_id')
            ->where('users.admin_user_id',Admin::user()->isRole('agents') ? Admin::user()->id : 0)
            ->select('payment_docs.*')->orderBy('id','desc')->paginate();

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('user.name', '用户');
            $filter->equal('order.no', '订单号');
        });
        $grid->selector(function (Grid\Tools\Selector $selector) {
            $selector->select('state', '处理状态筛选', [
                '' => '全部',
                0 => '未处理',
                1 => '处理中',
                2 => '已完成',
            ]);
        });
        $grid->column('id', 'Id');
        $grid->column('user.name', '用户');
        $grid->column('order.no', '订单编号');
        $grid->column('order.amount', '金额')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('order.platform', '支付平台');
        $grid->column('order.payment_no', '平台单号');
        $grid->column('order.state', '状态')->display(function ($var) {
            switch ($var) {
                case 0:
                    return '进行中';
                    break;
                case 1:
                    return '成功';
                    break;
                case 2:
                    return '失败';
                    break;
            }
        });
        $grid->image_url('支付凭证')->lightbox(['width' => 50, 'height' => 50]);
        $grid->column('state', '处理状态(点击修改)')
            ->radio([0 => '未处理', 1 => '处理中'])
            ->dot([0 => 'danger', 1 => 'warning'], 'danger');
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '更新时间');
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
        });
        $grid->disableExport();
        $grid->disableCreateButton();
        $grid->disableRowSelector();
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
        $show = new Show(PaymentDoc::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('order_id', __('Order id'));
        $show->field('image_url', __('Image url'));
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
        $form = new Form(new PaymentDoc());

        $form->number('user_id', __('User id'));
        $form->number('order_id', __('Order id'));
        $form->text('image_url', __('Image url'));
        $form->switch('state', __('State'));

        return $form;
    }
}
