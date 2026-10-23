<?php

namespace App\Admin\Controllers;

use App\Jobs\GoogleTrans;
use App\Models\Configuration;
use App\Models\Red;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Route;

class ConfigurationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '配置';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Configuration());
        $grid -> model()->orderBy('id','asc');
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

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->column('id', __('Id'));
        $grid->column('type', '类型');
        $grid->column('key', '键');
        $grid->column('value', '值')->display(function ($var) {
            if ('money' === $this->type) {
                return $var / 100;
            }
            if(in_array($this->key,Configuration::$autotranskey)){
                return "[<a href='/admin/trans-admin?key=configuration.{$this->key}' target='_blank'>多语言</a>]";
            }
            return $var;
        });
        $grid->column('describe', '描述');

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
        $show = new Show(Configuration::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('type', __('Type'));
        $show->field('key', __('Key'));
        $show->field('value', __('Value'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Configuration());
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });
        if (Route::currentRouteName() == 'admin.configurations.edit') {
            $form->select('type', '类型')->options([
                'bool' => '布尔',
                'string' => '字符串',
                'int' => '整型',
                'array' => '数组',
                'json' => 'json',
                'money' => '金额',
                'text' => '文本',
            ])->readonly();
            $form->text('key', '键')->readonly();
            $form->text('value', '值')->help('数组逗号分隔');
            $form->text('describe', '描述')->readonly();
        } else {
            $form->select('type', '类型')->options([
                'bool' => '布尔',
                'string' => '字符串',
                'int' => '整型',
                'array' => '数组',
                'json' => 'json',
                'money' => '金额',
                'text' => '文本',
            ]);
            $form->text('key', '键');
            $form->text('value', '值')->help('数组逗号分隔');
            $form->text('describe', '描述');
        }

        $form->radio('autotrans', '自动翻译')
            ->options([
                1 => '是',
                0 => '否',
            ])->default(1);
        $form->ignore(['autotrans']);

        $form->editing(function (Form $form) {
            if ($form->model()->type === 'money') {
                $form->model()->value = $form->model()->value / 100;
            }
        });

        $form->saving(function (Form $form) {
            if ('money' === $form->type) {
                $form->value = $form->value * 100;
            }
        });

        $form->saved(function (Form $form) {
            $key = $form->model()->key;
            $autotrans = \request('autotrans');
            if($autotrans == 1){
                $transkey = "configuration.".$key;
                $value = $form->value;
                GoogleTrans::dispatch(2, $transkey, $value, "")->onQueue(env('REDIS_QUEUE_LOW', 'default'));
            }
        });


        return $form;
    }
}
