<?php

namespace App\Admin\Controllers;

use App\Admin\Render\ShowOrder;
use App\Models\Order;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderAgainController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '今日复充用户';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order());

        $grid->disableExport();
        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableActions();
        //$grid->disableFilter();

        $data = \request()->all();

        $search_date = Carbon::today();
        if (!empty($data) && isset($data['created_date'])) {
            $search_date = Carbon::parse($data['created_date']);
        }
        $grid->model()->join('users','orders.user_id','users.id')
            ->with(['user'=>function($q){
                $q->with('orderRecharge');
            }])
            ->where('users.admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)
            ->selectRaw('orders.user_id as id,orders.user_id')
            ->where(['type' => 1, 'state' => 1,'iscount'=>0])
            ->whereIn('orders.user_id', function ($query) use($search_date){
                $query->select('orders.user_id')->from('orders')->where(['type' => 1, 'state' => 1,'iscount'=>0])
                ->whereDate('orders.created_at', '=' , $search_date);
            })
        ->whereDate('orders.created_at', '<', $search_date)
        ->groupBy('orders.user_id');
        //->havingRaw('count(*)>1');
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->where(function ($query) {
            }, '日期', 'created_date')->date();

            $filter->expand();
        });
        $grid->column('id', __('用户id'));
        $grid->column('user.name', '用户名');
        $grid->column('user_recharge_show', '充值记录')->modal('充值记录', ShowOrder::class);
        $grid->column('orderRecharge', '充值')->display(function ($var) {
            $str = $this->user->orderRecharge->sum('amount')/100 . "({$this->user->orderRecharge->count()}次)";
            return $str;
        });
        return $grid;
    }
}
