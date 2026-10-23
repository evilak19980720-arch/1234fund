<?php

namespace App\Admin\Controllers;

use App\Jobs\GoogleTrans;
use App\Models\Pet;
use App\Models\RedLadder;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\MessageBag;

class RedLadderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '阶梯红包';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RedLadder());
        $grid->model()
            ->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)
            ->orderBy('id', 'asc');
        $grid->column('id', __('Id'));
        $grid->column('level', '等级');
        $grid->column('red_withdrew_value', '满额红包')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('red_value', '初始红包')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('red_invite_num', '所需邀请人数');
        $grid->column('pet.name', '提现所需持有宠物');
        $grid->column('subordinate_pet.name', '下级所需持有宠物');
        $grid->column('subordinate_num', '下级所需持有人数');

        $grid->column('invitation_copy', '邀请文案')->display(function ($var){
            return $var . "[<a href='/admin/trans-admin?key=red_ladders.invitation_copy.{$this->id}' target='_blank'>多语言</a>]";
        });

        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '更新时间');

        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
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
        $form = new Form(new RedLadder());
        $form->tools(function (Form\Tools $tools) {
            // 去掉`查看`按钮
            $tools->disableView();
            // 去掉`删除`按钮
            $tools->disableDelete();
        });
        $form->editing(function ($form) {
            $form->model()->red_withdrew_value /= 100;
            $form->model()->red_value /= 100;
        });
        $form->setWidth('5', 3);
        $form->text('level', '红包等级')->required();
            //->creationRules(['required', 'unique:red_ladders,level'])
            //->updateRules(['required', 'unique:red_ladders,level,{{id}}']);
        $form->text('red_withdrew_value', '满额红包')->required();
        $form->text('red_value', '初始红包')->required();
        $form->text('red_invite_num', '所需邀请人数')->required();
        $form->select('pet_id', '提现所需持有宠物')->options($this->pets());
        $form->select('subordinate_pet_id', '下级所需持有宠物')->options($this->pets());
        $form->text('subordinate_num', '下级所需持有人数')->required();
        $form->switch('state', '是否启用')->default(1);

        $form->text('invitation_copy', '邀请文案')->required();
        $form->radio('autotrans', '自动翻译')
            ->options([
                1 => '是',
                0 => '否',
            ])->default(1);
        $form->ignore(['autotrans']);


        if (Admin::user()->isRole('administrator')) {
            $form->hidden('admin_user_id')->value(0);
        }
        if (Admin::user()->isRole('agents')) {
            $form->hidden('admin_user_id')->value(Admin::user()->id);
        }

        $form->saving(function ($form) {
            if ($form->red_invite_num > ($form->red_withdrew_value - $form->red_value)*100)
            {
                $error = new MessageBag([
                    'title'   => '出错啦',
                    'message' => '所需邀请人数不能大于'.($form->red_withdrew_value - $form->red_value)*100
                ]);
                return back()->with(compact('error'))->withInput();
            }
            if ($form->isCreating()) {
                if (RedLadder::query()->where('admin_user_id','=',$form->admin_user_id)->where('level','=',$form->level)->count()) {
                    $error = new MessageBag([
                        'title'   => '出错啦',
                        'message' => '该等级的红包已存在'
                    ]);
                    return back()->with(compact('error'))->withInput();
                }
                $red_ladder = RedLadder::query()->where('admin_user_id','=',$form->admin_user_id)->orderBy('level','desc')->first();
                if ((!$red_ladder && $form->level != 1) || ($form->level - 1 != $red_ladder->level)) {
                    $error = new MessageBag([
                        'title'   => '出错啦',
                        'message' => '不可跨等级添加红包'
                    ]);
                    return back()->with(compact('error'))->withInput();
                }
            }

            $form->red_withdrew_value *= 100;
            $form->red_value *= 100;
        });
        $form->tools(function ($tools) {
            $tools->disableDelete();
        });

        $form->saved(function (Form $form) {
            $id = $form->model()->id;
            $autotrans = \request('autotrans');
            if($autotrans == 1){
                $key = "red_ladders.invitation_copy.".$id;
                $value = $form->invitation_copy;
                GoogleTrans::dispatch(2, $key, $value, "")->onQueue(env('REDIS_QUEUE_LOW', 'default'));
            }
        });
        return $form;
    }


    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(RedLadder::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('amount', __('Amount'));
        $show->field('from', __('From'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    public function pets()
    {
        if (Admin::user()->isRole('agents')) {
            $pets =  Pet::where('state','=',1)->where('pets.admin_user_id','=',Admin::user()->id)->get();
        }else{
            $pets =  Pet::where('state','=',1)->where('pets.admin_user_id','=',0)->get();
        }
        $arr = [];
        foreach ($pets as $pet) {
            $arr[$pet->id] = $pet->name;
        }
        return $arr;
    }
}
