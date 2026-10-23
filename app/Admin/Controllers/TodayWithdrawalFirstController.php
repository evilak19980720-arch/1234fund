<?php

namespace App\Admin\Controllers;

use App\Admin\Models\TodayWithdrawalFirst;
use App\Admin\Render\ShowOrder;
use App\Models\Order;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Godruoyi\Snowflake\Snowflake;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Carbon;

class TodayWithdrawalFirstController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '今日首次提现';
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order());

        $this_date = Request()->get('this_date');
        $parent_phone = Request()->get('parent_phone');
        $admin_user_id = Admin::user()->isRole('agents') ? Admin::user()->id : 0;
        //查会员是否存在
        $parent_userid = 0;
        if(!empty($parent_phone)){
            $parent_user = User::where('phone','=',$parent_phone)->first();
            if($parent_user){
                $parent_userid = $parent_user->id;
            }
        }
        $check_date = Carbon::parse($this_date)->toDateString();  //日期

        $query = $grid->model()
            ->with('user')
            ->whereIn('user_id', function ($query) use ($admin_user_id,$parent_userid) {
                $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                if(!empty($parent_userid)){
                    $query->where('relation', 'regexp', ',' . $parent_userid . ',$');
                }
            })
            ->where('type', '=', Order::WITHDRAWAL)
            ->whereNotNull('payment_no')
            ->where('state', '=', Order::SUCCESS)
            ->whereNotIn('user_id', function ($query) use ($check_date, $admin_user_id,$parent_userid) {
                $query->select('user_id')->from('orders')
                    ->when(!empty($parent_userid),function ($query)use($admin_user_id,$parent_userid){
                        $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                        if(!empty($parent_userid)){
                            $query->where('relation', 'regexp', ',' . $parent_userid . ',$');
                        }
                    })
                    ->where('type', '=', Order::WITHDRAWAL)
                    ->where('state', '=', Order::SUCCESS)
                    ->whereDate('created_at', '<', $check_date);
            })
            ->whereDate('created_at', '=', $check_date)
            ->groupBy('user_id')
            ->orderBy('id');



        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableActions();
        //$grid->disablePagination();
        //$grid->disableExport();
        $grid->showExportBtn();
        //$grid->disableFilter();

        $grid->filter(function($filter){
            $filter->disableIdFilter();
            //$filter->date('created_at', '日期')->date();
            //$filter->like('parent_phone', '收款人');
            $filter->where(function ($query) {
                //$query->where('created_at', '>', "1970-01-01");
            }, '日期','this_date')->date();
            $filter->where(function ($query) {
                //$query->where('created_at', '>', "1970-01-01");
            }, '收款人','parent_phone');
            $filter->expand();
        });

        $grid->column('id', __('Id'));
        $grid->column('user.name', '用户');
        $grid->column('amount', '金额')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('handlingfee', '手续费')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('actual_amount', '实际到账')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('currery_amount', '货币金额')->display(function ($var) {
            return $this->currery ." ". $var / 100;
        });
        $grid->column('no', '订单号');
        $grid->column('platform', '支付平台');
        $grid->column('payment_no', '交易单号')->width(150);
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '更新时间');

        $grid->header(function ($query) {
            $list = app(TodayWithdrawalFirst::class)->paginate();
            return '总数量:'.$list->count().',总金额:'.$list->sum('actual_amount') / 100;
        });

        return $grid;
    }


}
