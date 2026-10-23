<?php

namespace App\Admin\Controllers;
/*
 * 众筹购买记录
 * */
use App\Models\Crowd;
use App\Models\CrowdsOrder;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CrowdOrderController extends AdminController
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
        $grid = new Grid(new CrowdsOrder());
        $grid->model()->with([
                'crowd.pet' => function ($query) {
                    $query->select('pets.id', 'name');
                }
            ])
            ->join('users','crowds_orders.user_id','users.id')
            ->where('users.admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)
            ->orderBy('crowds_orders.id', 'desc')->paginate();
        $grid->disableExport();
        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableRowSelector();


        $grid->quickSearch(function ($model, $query) {
            $model->join('users','crowds_orders.user_id','users.id')
                ->where('users.admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0)
                ->where('users.phone', $query)
                ->select('crowds_orders.*', 'users.phone');
        })->placeholder('输入手机号');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('robot', '机器人')->select([
                0 => '否',
                1 => '是'
            ]);
            $filter->like('user.phone', '手机号');
            $filter->between('created_at', '创建时间')->datetime();
            $filter->equal('crowd_id', '彩种')->select($this->selectCrowd());
            $filter->equal('issue', '期数');
            $filter->equal('win', '中奖')->select([
                0 => '否',
                1 => '是'
            ]);

        });

        $grid->column('id', __('Id'));
        $grid->column('user.phone', __('用户名'));
        $grid->column('robot', '机器人')->bool();
        $grid->column('crowd', '彩种')->display(function ($var) {
            return $var['pet']['name'].'['.$var['id'].']';
        });
        $grid->column('issue', '期数');
        $grid->column('portion', '购买份额');
        $grid->column('amount', '购买金额')->display(function ($var) {
            return $var / 100;
        });
        $grid->column('win', '中奖')->bool();
        $grid->column('status', '买出')->display(function ($var) {
            $arr = [0 => '未操作', 1 => '饲养', 2 => '转让'];
            return $arr[$var];
        });
        $grid->column('created_at', __('创建时间'));
        $grid->column('updated_at', __('更新时间'));
        /*
        $grid->column('pet.name', '宠物');
        $grid->column('portion', '份额');
        $grid->column('time','时间')->display(function () {
            return sprintf('%s - %s',$this->start,$this->end);
        });
        $grid->column('interval', '间隔(分)');
        $grid->column('state', '状态')->bool();
        $grid->column('sort', '排序');
        //$grid->column('robot', '机器人')->bool();
        //$grid->column('ratio', '比例');
        $grid->column('win', '机器人胜率');
        $grid->column('rate', '转让费率');
        */
        return $grid;
    }

    public function selectCrowd()
    {
        $arr = [];
        $crowds = Crowd::with('pet')->get();
        foreach ($crowds as $crowd) {
            $arr[$crowd->id] = $crowd->pet['name'].'['.$crowd->id.']';
        }
        return $arr;
    }

}
