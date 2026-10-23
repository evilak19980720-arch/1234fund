<?php

namespace App\Admin\Controllers;

use App\Models\Crowd;
use App\Models\Fund;
use App\Models\FundOrder;
use App\Models\FundPeriod;
use App\Models\Issue;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;

class FundPeriodController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '排期';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new FundPeriod());
        $grid->model()
            ->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)
            ->with(['pet','fundOrder' => function($query){
                $query->select('id','fund_period_id')->groupBy('user_id');
            }])
            ->withCount(['fundOrder as fund_order_total_amount' => function($query) {
                $query->select(DB::raw("sum(amount)"));
            }])
            ->orderBy('id', 'desc')->paginate();
        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('fund_id', '彩种')->select($this->selectCrowd());
            $filter->equal('issue', '期数');
        });
        $grid->column('id', __('Id'));
        $grid->column('pet.name', '彩种');
        $grid->column('issue', '期号');
        $grid->column('fund_order', '购买人数')->display(function($val){
            return count($val);
        });
        $grid->column('fund_order_total_amount', '购买金额')->display(function ($val){
            return $val / 100;
        });
//        $grid->column('total_price', '总价')->display(function ($var) {
//            return $var / 100;
//        });
        $grid->column('time','参与时间')->display(function () {
            return sprintf('%s - %s',$this->start_time, $this->end_time);
        });
//        $grid->column('draw', '开奖')->bool();
        $grid->column('draw_number','设置结果')->display(function ($val,$column){
            if (time() < Carbon::parse($this->end_time)->timestamp && time() - Carbon::parse($this->end_time)->timestamp < 30) {
                return $column->editable('select', range(0,9));
            } else {
                return $val;
            }
        });
        //
        $grid->column('draw_size','开奖结果(数字/大小/模式)')->display(function () {
            if (!$this->draw) {
                return '未开奖';
            } else {
                return $this->draw_number .' / '.$this->draw_size .' / '.FundPeriod::DRAW_PATTERN[$this->draw_pattern];
            }
        });
        return $grid;
    }


    protected function form()
    {
        $form = new Form(new FundPeriod());
        $form->saving(function (Form $form) {
            //1.判断是否满足修改条件
            if (time() > Carbon::parse($form->model()->end_time)->timestamp) {
                return response()->json([
                    'status'  => false,
                    'message' => '当前期已结束，不可设置',
                ]);
            }

            if (Carbon::parse($form->model()->end_time)->timestamp - time() < 30) {
                return response()->json([
                    'status'  => false,
                    'message' => '距离结束不足30秒，不可设置',
                ]);
            }
            $form->draw_number = (int)$form->draw_number;
            $form->model()->draw_number = (int)$form->draw_number;
            $form->model()->draw_pattern = FundPeriod::SYSTEM;
            $form->model()->draw_size = FundPeriod::bigOrSmall((int)$form->draw_number);
        });
        $form->saved(function (Form $form) {
            //将此排期的参与记录进行更新中奖结果
            (new FundOrder())->updateIsWin($form->model()->id);
        });
        return $form;
    }

    public function selectCrowd()
    {
        $arr = [];
        $crowds = Fund::with('pet')->where('admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)->get();
        foreach ($crowds as $crowd) {
            $arr[$crowd->id] = $crowd->pet['name'].'['.$crowd->id.']';
        }
        return $arr;
    }

}
