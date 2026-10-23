<?php

namespace App\Admin\Controllers;

use App\Models\Statistics;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class InstallCountController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '安装统计';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Statistics());
        $grid->model()
            ->selectRaw('count(*) as install_count,DATE_FORMAT(created_at,"%Y-%m-%d") as date,type')
            ->groupBy('date', 'type')
            ->orderBy('date', 'desc')
            ->paginate();
        $grid->column('type', '设备类型');
        $grid->column('install_count', '安装数量');
        $grid->column('date', '日期');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->between('created_at', '时间段')->datetime();
        });
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableColumnSelector();
        return $grid;
    }

}
