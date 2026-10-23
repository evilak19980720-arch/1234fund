<?php

namespace App\Admin\Controllers;

use App\Models\Phone;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Env;
use App\Admin\Actions\Sharephone\Tranimport;

class SharephoneController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '分享号码';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Phone());
        $grid->model()->orderBy('id', 'desc');
        $grid->quickSearch('phone')->placeholder('输入号码');
        $grid->column('id', __('Id'));
        $grid->column('phone', '号码');
        $grid->column('state', '状态')->display(function ($var) {
            if($this->state == 1){
                return "已分享";
            }else{
                return '未分享';
            }

        });
        $grid->column('send_at', '发送时间');

        //$grid->disableRowSelector();
        $grid->disableExport();
        $grid->disableFilter();
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            //$actions->disableDelete();
        });

        // 将导入操作加入到表格的工具条中
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new Tranimport());
        });

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
        $show = new Show(Message::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('ticker', __('Ticker'));
        $show->field('title', __('Title'));
        $show->field('text', __('Text'));
        $show->field('after_open', __('After open'));
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
        $form = new Form(new Phone());

        //$form->text('ticker', '提示')->required();
        //$form->text('title', '标题')->required();
       // $form->textarea('text', '内容')->required();
//        $form->text('after_open', '打开后')->default('go_app');


        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        return $form;
    }
}
