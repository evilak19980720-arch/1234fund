<?php

namespace App\Admin\Actions\CollectTrctask;

use App\Jobs\PayOrder;
use App\Models\Configuration;
use App\Models\Order;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class ViewAddress extends RowAction
{
    public $name = '查看详情';

    public function href()
    {

        return "/admin/collect-trctask-logs?collect_trctask_id=".$this->getKey();
    }



}
