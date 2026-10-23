<?php


namespace App\Admin\Render;

use App\Models\Order;
use Encore\Admin\Widgets\Table;
use Illuminate\Contracts\Support\Renderable;

class ShowOrder implements Renderable
{

    /**
     * @inheritDoc
     */
    public function render($key = null)
    {
        $orders = Order::where('user_id', $key)->where('type','=',1)->where('state','=',1)->get()->map(function ($orders) {
            return $orders->only(['amount', 'created_at']);
        });
        $data = [];
        if ($orders->isNotEmpty()) {
            foreach ($orders as $order) {
                array_push($data, [$order['amount'] / 100, $order['created_at']]);
            }
        }
        echo new Table(['金额', '时间'], $data);
    }
}
