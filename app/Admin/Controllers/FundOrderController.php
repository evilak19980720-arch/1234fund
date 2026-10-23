<?php

namespace App\Admin\Controllers;
/*
 * 众筹购买记录
 * */
use App\Models\Fund;
use App\Models\FundOrder;
use App\Models\FundPeriod;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;

class FundOrderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '购买记录';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new FundOrder());
        $grid->model()->with(['fundPeriod.pet','user'])
            ->whereHas('user',function($query){
                $query->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0);
            })
            ->latest()
            ->paginate();
        $grid->disableExport();
        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableRowSelector();


//        $grid->quickSearch(function ($model, $query) {
//            $model->join('users','crowds_orders.user_id','users.id')
//                ->where('users.admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)
//                ->where('users.phone', $query)
//                ->select('crowds_orders.*', 'users.phone');
//        })->placeholder('输入手机号');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('user.phone', '手机号');
            $filter->between('created_at', '创建时间')->datetime();
            $filter->equal('fundPeriod.pet_id', '彩种')->select($this->selectCrowd());
            $filter->equal('fundPeriod.issue', '期数');
            $filter->equal('win', '中奖')->select([
                0 => '否',
                1 => '是'
            ]);

        });

        $grid->column('id', __('Id'));
        $grid->column('user.phone', __('用户名'));
        $grid->column('buy', __('购买值'));
        $grid->column('quantity','购买数量');
        $grid->column('fundPeriod.pet', '彩种')->display(function ($var) {
            if (!$var) {
                return '未查询到彩种';
            } else {
                return $var['name'].'['.$var['id'].']';
            }

        });
        $grid->column('fundPeriod.issue', '期数');
        $grid->column('draw_size','开奖结果(数字/大小)')->display(function () {
            if (!$this->fundPeriod->draw) {
                return '未开奖';
            } else {
                return $this->fundPeriod->draw_number .' / '.$this->fundPeriod->draw_size;
            }
        });
        $grid->column('amount', '购买金额')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('win', '中奖')->bool();
        $grid->column('status', '买出')->display(function ($var) {
            $arr = [0 => '未操作', 1 => '饲养', 2 => '转让'];
            return $arr[$var];
        });
        $grid->column('created_at', __('创建时间'));
        return $grid;
    }

    public function selectCrowd()
    {
        $arr = [];
        $funds = Fund::with('pet')->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)->get();
        foreach ($funds as $fund) {
            $arr[$fund->pet['id']] = $fund->pet['name'].'['.$fund->pet['id'].']';
        }
        return $arr;
    }

}
