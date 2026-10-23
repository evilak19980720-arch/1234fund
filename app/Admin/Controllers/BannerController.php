<?php

namespace App\Admin\Controllers;

use App\Models\Banner;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class BannerController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Banner';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Banner());
        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();
        // 禁用筛选
        $grid->disableFilter();

        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->column('id', __('Id'));
        $grid->column('type', '类型')->display(function ($var) {
            $arr = [
                0 => "首页",
                1 => "活动页",
                2 => "下载页"
            ];
            return $arr[$var];
        });
        $grid->path('图片')->lightbox(['width' => 50, 'height' => 50]);
        $grid->column('url', '链接')->link();

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
        $show = new Show(Banner::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('path', __('Path'));
        $show->field('url', __('Url'));
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
        $form = new Form(new Banner());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        $form->select('type', '类型')->options([
            0 => "首页",
            1 => "活动页",
            2 => "下载页",
        ]);
        $form->image('path', '图片')->uniqueName();
        $form->text('url', '链接')->help('/team 进入团队介绍页面
        /share进入分享页
        /teamDesc进入团队介绍页面
        /vip进入VIP页
/investment进入投资界面
/my进入个人中心
/Recharge进入充值界面');


        return $form;
    }
}
