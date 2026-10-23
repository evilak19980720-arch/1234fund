<?php


namespace App\Admin\Actions\User;


use Encore\Admin\Actions\RowAction;

class JumpTo extends RowAction
{
    public $name = '详情';

    public function href()
    {
        return 'user-detail/' . $this->getKey();
    }
}
