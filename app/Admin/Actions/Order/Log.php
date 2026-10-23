<?php

namespace App\Admin\Actions\Order;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class Log extends RowAction
{
    public $name = '日志';

    public function href()
    {
        return '/admin/users';
    }

}
