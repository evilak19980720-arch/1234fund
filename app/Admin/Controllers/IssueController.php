<?php

namespace App\Admin\Controllers;

use App\Models\Crowd;
use App\Models\Issue;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class IssueController extends AdminController
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
        $grid = new Grid(new Issue());
        $grid->model()->select('issues.*')->orderBy('id', 'desc')
            ->join('crowds','issues.crowd_id','crowds.id')
            ->join('pets','pets.id','crowds.pet_id')
            ->where('pets.admin_user_id','=',Admin::user()->isRole('agents') ? Admin::user()->id : 0);
        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('crowd_id', '彩种')->select($this->selectCrowd());
            $filter->equal('issue', '期数');
        });

        $grid->model()->with(['crowd.pet' => function ($query) {
            $query->select('pets.id', 'name');
        }])
            ->orderBy('id','desc')
            ->paginate();

        $grid->column('id', __('Id'));
        $grid->column('crowd.pet', '彩种')->display(function ($var) {
            return $var['name'];
        });
        $grid->column('issue', '期数');
        $grid->column('time','开奖时间')->display(function () {
            return sprintf('%s - %s',$this->start_time, $this->end_time);
        });
        $grid->column('draw', '开奖')->bool();
        $grid->column('portion', '份额');
        $grid->column('bought', '已买份额');
        $grid->column('total_price', '总价')->display(function ($var) {
            return $var / 100;
        });


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
