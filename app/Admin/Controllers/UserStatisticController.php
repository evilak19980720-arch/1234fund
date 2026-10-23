<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\UserStatistic\JumpTo;
use App\Admin\Actions\UserStatistic\Update;
use App\Models\User;
use App\Models\UserStatistic;
use Encore\Admin\Actions\Action;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Widgets\Tab;
use Illuminate\Http\Request;

class UserStatisticController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '会员统计';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UserStatistic());
        $data = \request()->all();
        $query = $grid->model()->with('user')
            ->whereHas('user',function ($query){
                $query->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0);
            });
        if (!empty($data) && isset($data['__search__'])) {
            $query->orderBy('created_at', 'desc')->paginate();
        } elseif (Admin::user()->isRole('servers')) {
            // 3 是为了不让查到 0和1
            $query->where('id','<',0)->orderBy('created_at', 'desc')->paginate();
        } else {
            $query->orderBy('created_at', 'desc')->paginate();
        }
        if (Admin::user()->isRole('servers')) {
            $grid->disableCreateButton();
            $grid->disableFilter();
        }
        // 禁用导出
        $grid->disableExport();
        $grid->disableCreateButton();
        $grid->tools(function ($tools) {
            // 禁用批量删除按钮
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->actions(function ($actions) {
            $actions->add(new JumpTo());
            $actions->disableEdit();
            $actions->disableView();
            $actions->disableDelete();
        });
        $grid->batchActions(function ($batch) {
            $batch->add(new Update());
        });
        $grid->quickSearch('phone')->placeholder('输入手机号');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->like('phone', '手机');
                $filter->between('register_time', '注册时间')->datetime();
            });
            $filter->column(1 / 2, function ($filter) {


            });
        });
        $this->column($grid);
        return $grid;
    }


    public function userStatisticDetail($id,Request $request)
    {
        $tab = new Tab();
        $count = count($request->all());
        $active = array_keys($request->all())[$count - 1];
        $tab->add('一级', $this->level($id), $active == 'tab1');
        $tab->add('二级', $this->level($id, 2), $active == 'tab2');
        $tab->add('三级', $this->level($id, 3), $active == 'tab3');
        echo $tab->render();
    }


    public function level($id, $level = 1, $tab = 'tab1')
    {
        $grid = new Grid(new UserStatistic());
        switch ($level) {
            case 1:
                $index = -2;
                break;
            case 2:
                $index = -3;
                $tab = 'tab2';
                break;
            case 3:
                $index = -4;
                $tab = 'tab3';
                break;
        }

        $user_id_arr = User::query()->where('relation', 'regexp', sprintf(',%d,%s$', $id, str_repeat('[0-9]+,', $level - 1)))->pluck('id');
        $grid->model()->whereIn('user_id',$user_id_arr->toArray())->paginate(15, ['*'], $tab);
        $this->column($grid);
        $grid->tools(function ($tools) {
            /*
                $tools->append(<<<EOT
    <button id="back" class="btn btn-sm btn-default" onclick="javascript:history.back()">返回</button>
    EOT
                );
            */
        });
        $grid->disableActions();
        $grid->disableColumnSelector();
        $grid->disableRowSelector();
        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableExport();
        return $grid->render();
    }

    protected function column(&$grid)
    {
        $grid->column('id', __('Id'));
        $grid->column('user_id', '会员id');
        $grid->column('phone', '手机');
        $grid->column('recharge', '充值金额')->display(function ($var) {
            return $var / 100;
        })->sortable();
        $grid->column('today_recharge', '今日充值金额')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('commission', '返佣金额')->display(function ($var) {
            return $var / 100;
        })->sortable();
        $grid->column('one_level', '一级下级人数');
        $grid->column('two_level', '二级下级人数');
        $grid->column('three_level', '三级下级人数');
        $grid->column('subordinate_total', '下级总人数')->sortable();
        $grid->column('subordinate_recharge', '下级充值总额')->display(function ($var) {
            return $var / 100;
        })->sortable();
        $grid->column('subordinate_today_recharge', '下级今日充值总额')->display(function ($var) {
            return $var / 100;
        })->sortable();
        $grid->column('register_time', '注册时间');
        $grid->column('updated_at', '更新时间');
    }
}
