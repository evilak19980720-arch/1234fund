<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Friend\Audit;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Friend;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FriendController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '朋友圈';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Friend());
        $grid->model()->with('user')
            ->whereHas('user',function($sql){
            $sql->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0);
        })->select('friends.*')
            ->orderBy('id', 'desc')
            ->paginate();
        $grid->column('id', __('Id'));
        $grid->column('user.name', '用户');
        $grid->imgs('图片')->pluck('img')->lightbox(['width' => 50, 'height' => 50]);
        $grid->column('content', '内容')->width(400)->display(function ($var) {
            return mb_strlen($var) > 20 ? sprintf('%s...', $var) : $var;
        });
        $grid->column('times', '点赞数')->display(function ($var) {
            return $this->up ? count(explode(',', $this->up))+$var:$var;
        });
        $grid->column('up', '点赞数(真实)')->display(function ($var) {
            return $var ? count(explode(',', $var)):0;
        });
        $grid->column('ok', '审核')->bool();
        $grid->column('reward', '奖励')->bool();
        $grid->column('created_at', '发布时间');

        $grid->disableExport();
        $grid->disableFilter();
        $grid->disableCreateButton();

        $grid->batchActions(function ($batch) {
            $batch->add(new Audit());
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
        $show = new Show(Friend::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('content', __('Content'));
        $show->field('up', __('Up'));
        $show->field('ok', __('Ok'));
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
        $form = new Form(new Friend());

        $form->number('user.name', '用户')->disable();
        $form->text('content', '内容');
        $form->multipleFile('imgs', '图片')->pathColumn('img')->removable()->disable();
        $form->number('times','假点赞数')->min(0);
        /*
        $form->switch('ok', '审核')->states([
            'on' => ['value' => 1, 'text' => '通过', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '拒绝', 'color' => 'danger'],
        ]);
        */
/*
        if ($form->isEditing()) {
            $form->editing(function (Form $form) {
                // 已经人工操作过
                if ($form->model()->ok) {
                    $form->disableReset();
                    $form->disableSubmit();
                }
            });
        }
*/
        /*
        $form->saved(function (Form $form) {
            if ($form->model()->ok) {
                $amount = Configuration::getItem('friends_reward');
                if ($form->model()->ok == 1 && $amount > 0) {
                    if(!Account::where('type','=',Account::FRIENDS_REWARD)->where('to','=',$form->model()->user_id)->first()) {
                        User::where('id', '=', $form->model()->user_id)->increment('balance', $amount);
                        Account::create([
                            'order_id' => 0,
                            'type' => Account::FRIENDS_REWARD,
                            'to' => $form->model()->user_id,
                            'form' => 0,
                            'amount' => $amount,
                            'level' => 0
                        ]);
                    }
                }
            }
        });
        */

        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        return $form;
    }
}
