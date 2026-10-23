<?php

namespace App\Admin\Controllers;

use App\Models\Message;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Hedeqiang\UMeng\Android;
use Illuminate\Support\Env;

class MessageController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '消息推送';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Message());

        $grid->column('id', __('Id'));
        $grid->column('ticker', '提示');
        $grid->column('title', '标题');
        $grid->column('text', '内容');
//        $grid->column('after_open', '打开后');
        $grid->column('created_at', '发送时间');

        $grid->disableRowSelector();
        $grid->disableExport();
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
        $form = new Form(new Message());

        $form->text('ticker', '提示')->required();
        $form->text('title', '标题')->required();
        $form->textarea('text', '内容')->required();
//        $form->text('after_open', '打开后')->default('go_app');

        $form->saved(function (Form $form) {
            $android = new Android([
                'appKey' => Env::get('UMENG_KEY'),
                'appMasterSecret' => Env::get('UMENG_SECRET'),
                'production_mode' => true
            ]);

            $response = $android->sendAndroidBroadcast([
                'ticker' => $form->model()->ticker,
                'title' => $form->model()->title,
                'text' => $form->model()->text,
                'after_open' => 'go_app',
                "mi_activity"=> "com.reny.szkj.catapp.ui.UmengPushActivity",
                "mipush"=> true,
            ]);
            $form->model()->ret = $response;
            $form->model()->save();
        });

        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        return $form;
    }
}
