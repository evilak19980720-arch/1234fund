<?php

namespace App\Admin\Controllers;

use App\Models\Red;
use App\Models\RedLog;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Carbon;

class RedLogController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '红包记录';


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Red());

        $grid->model()->with('user')
            ->whereHas('user',function($sql){
                $sql->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0);
            })->where('reds.user_id', '!=', 0)
            ->select('reds.*')
            ->orderBy('created_at', 'desc')
            ->paginate();
        $grid->column('id', __('Id'));
        $grid->column('user_id', __('用户id'));
        $grid->column('user.name', __('用户名'))->expand(function ($model) {
            $red_log = $model->red_log()->where('red_id','=',$this->id)->withCount('orders')->get()->map(function ($red_log) {
                return $red_log->only(['id', 'from', 'users', 'amount', 'created_at','orders_count']);
            });
            $red_log = $red_log->toArray();
            $data = [];
            if ($red_log) {
                foreach ($red_log as $k => $v) {
                    $data[$k]['id'] = $v['id'];
                    $data[$k]['from'] = $v['from'];
                    $data[$k]['name'] = $v['users']['name'];
                    $data[$k]['amount'] = $v['amount'] / 100;
                    $data[$k]['orders_count'] = $v['orders_count'];
                    $data[$k]['created_at'] = $v['created_at'];
                }
            }
            return new Table(['ID', '被邀请用户Id', '被邀请用户名', '产生红包金额','交易数量', '邀请时间'], $data);
        });
        $grid->column('amount', __('当前红包金额'))->display(function ($var) {
            return $var / 100;
        });
        $grid->column('state', __('状态'))->display(function ($var) {
            $arr = [1 => '进行中', 2 => '满额', 3 => '已提现', 4 => '已过期'];
            return $arr[$var];
        });
        $grid->column('expires_at', __('过期时间'))->display(function ($var) {
            return Carbon::createFromTimeStamp($var)->toDateTimeString();
        });
        $grid->column('created_at', __('创建时间'));
        $grid->column('updated_at', __('更新时间'));
        $grid->quickSearch('user_id')->placeholder('请输入用户id');
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->equal('user.name', '用户名');
        });
        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableColumnSelector();
        $grid->disableBatchActions();
        $grid->selector(function (Grid\Tools\Selector $selector) {
            $selector->select('state', '红包状态', ['' => '全部', 1 => '进行中', 2 => '满额', 3 => '已提现', 4 => '已过期']);
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
        $show = new Show(RedLog::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('amount', __('Amount'));
        $show->field('from', __('From'));
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
        $form = new Form(new RedLog());

        $form->number('user_id', __('User id'));
        $form->number('amount', __('Amount'));
        $form->number('from', __('From'));

        return $form;
    }
}
