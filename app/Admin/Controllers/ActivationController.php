<?php

namespace App\Admin\Controllers;

use App\Models\Record;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use function Clue\StreamFilter\fun;

class ActivationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '激活统计';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Record());
        $grid->model()
            ->selectRaw('sum(number) as number,count(*) as device_count,date_format(created_at,"%Y-%m-%d") as date,type')
            ->groupBy('date','type')
            ->orderBy('date')
            ->paginate();
        $grid->column('type', '设备类型');
        $grid->column('device_count', '设备数量');
        $grid->column('number', '激活次数');
        $grid->column('date', '日期');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->between('created_at', '时间段')->datetime();
        });
        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableColumnSelector();
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
        return $grid;
    }
}
