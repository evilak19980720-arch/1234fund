<?php


namespace App\Admin\Render;

use App\Models\User;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Table;
use Illuminate\Contracts\Support\Renderable;

class ShowUser implements Renderable
{

    /**
     * @inheritDoc
     */
    public function render($key = null)
    {
        $tab = new Tab();
        $user = User::where('relation', 'like', '%' . $key . '%')->get();
        $data = [];
        foreach ($user as $item) {
            array_push($data, [$item->id, $item->name, $item->phone]);
        }
        $table = new Table(['Id', '用户名', '电话'], $data);
        $tab->add('下级', $table);
        $tab->add('tap1', '....');
        echo $tab->render();
    }
}
