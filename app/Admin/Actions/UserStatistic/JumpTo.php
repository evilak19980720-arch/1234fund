<?php


namespace App\Admin\Actions\UserStatistic;


use Encore\Admin\Actions\RowAction;

class JumpTo extends RowAction
{
    public $name = '下级详情';

    public function href()
    {
        return 'user-statistic-detail/' . $this->getRow()->user_id;
    }
}
