<?php

namespace App\Admin\Actions\Tran;

use Encore\Admin\Actions\RowAction;

class Newedit extends RowAction
{
    public $name = '编辑';

    public function href()
    {
        $id = $this->getKey();
        $row = $this->row();

        return "/admin/trans-admin/$id/edit?key={$row->key}";
    }
}
