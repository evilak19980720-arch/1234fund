<?php

namespace App\Admin\Controllers;

use App\Admin\Models\Activity;
use App\Jobs\GoogleTrans;
use App\Models\Pet;
use App\Models\Vip;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;

class ActivityController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '活动管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Activity());
        $grid->model()->with('adminUser')->orderBy('id', 'desc')->paginate();

        $grid->model()->where('admin_user_id', Admin::user()->isRole('agents') ? Admin::user()->id : 0);


        // 禁用CheckBox
        $grid->disableRowSelector();
        // 禁用导出
        $grid->disableExport();


        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
        $grid->actions(function ($actions) {
            if (Admin::user()->isRole('agents')) {
                $actions->disableDelete();
            }
            $actions->disableView();
        });

        if (Admin::user()->isRole('agents')) {
            // 禁用筛选
            $grid->disableFilter();
            $grid->disableCreateButton();
        }

        if (!Admin::user()->isRole('agents')) {
            $grid->filter(function ($filter) {
                $filter->disableIdFilter();
                $filter->equal('admin_user_id', '代理商')->select(
                    Administrator::query()->whereHas('roles', function ($query) {
                        $query->where('slug', 'agents');
                    })
                        ->pluck('name', 'id')
                );
            });
        }

        $grid->quickSearch('name')->placeholder('输入活动名称');

        $grid->column('id', __('Id'));
        $grid->column('name', '名称')->display(function ($var){
            return $var . "[<a href='/admin/trans-admin?key=activity.name.{$this->id}' target='_blank'>多语言</a>]";
        });
        //$grid->column('table', '关联表');
        $grid->column('type', '类型')->display(function ($value) {
            return Activity::TYPE_MAP[$value];
        });
        $grid->column('number', '需完成数量');
        $grid->column('status', '状态')->display(function ($value) {
            return $value == 1 ? '开启' : '关闭';
        });
        $grid->column('amount', '金额');

        if (Admin::user()->isRole('agents')) {
            $grid->column('sort', '排序')->text();
        } else {
            $grid->column('sort', '排序');
        }
        //        $grid->image('活动图片')->lightbox();
        $grid->column('created_at', '创建时间');
        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Activity::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('num', __('Num'));
        $show->field('amount', __('Amount'));
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
        $form = new Form(new Activity());
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
            //$form->display('table', '关联表');
            $form->select('type', '活动类型')->options(Activity::TYPE_MAP)->disable();
            $form->submitted(function ($form) {
                $form->ignore('type');
            });
        } else {
            //$form->select('table', '关联表')->options(Activity::ASSOCIATE_MAP)->required();
            $form->select('type', '活动类型')
                ->options(Activity::TYPE_MAP)
                ->default(Activity::INVITE_FRIENDS_AND_SUBORDINATE_GRAB)
                ->required();
        }

        $condition = Activity::query()->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)->pluck('name','id')->toArray();
        $condition['0'] = '无限制';
        ksort($condition);
        $form->select('condition', '需先完成的任务')->options($condition);
        //$form->select('pet_id', '购买产品')->options(Pet::query()->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)->pluck('name','id'));
        //$form->number('subordinate_buy_number', '多少个下级购买指定产品')->default(5)->rules('required|min:1');
        $form->text('name', '名称')->required();
        $form->radio('autotrans', '自动翻译')
            ->options([
                1 => '是',
                0 => '否',
            ])->default(1);
        $form->hidden('num', '数量')->default(1);
        $form->currency('amount', '金额')->required()->symbol('₹');
        $form->switch('status', '状态');
        //$form->text('description', '描述')->required();
        $form->number('sort', '排序')->default(0)->required();
        $form->number('number', '下级数量')->default(0)->required();

        $form->multipleSelect('vips', 'VIP')->required()->options(call_user_func(function () {
            $data = [];
            $list = Vip::getVip();
            foreach ($list as $val){
                $data[$val['id']] = $val['name'];
            }
            return $data;
        }));

        if (Admin::user()->isRole('administrator')) {
            $form->hidden('admin_user_id')->value(0);
        }
        if (Admin::user()->isRole('agents')) {
            $form->hidden('admin_user_id')->value(Admin::user()->id);
        }
        $form->saving(function (Form $form) {
            $form->pet_id = $form->pet_id ?: 0;

            if ($form->amount) {
                $form->amount = $form->amount * 100;
            }
        });

        $form->saved(function (Form $form) {
            $id = $form->model()->id;
            $autotrans = \request('autotrans');
            if($autotrans == 1){
                $key = "activity.name.".$id;
                $value = $form->name;
                GoogleTrans::dispatch(2, $key, $value, "")->onQueue(env('REDIS_QUEUE_LOW', 'default'));
            }
        });

        $form->ignore(['subordinate_number', 'invite_number','autotrans']);
        return $form;
    }
}
