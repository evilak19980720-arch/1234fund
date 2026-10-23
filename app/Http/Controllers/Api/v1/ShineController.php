<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Pay\ShinePayService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShineController extends Controller
{
    public function rechargeNotify(Request $request): string
    {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        $sign = $request->header('api-sign');
        $order = Order::where('no', '=', $data['body']['orderId'])->first();
        if (!$order) {
            return 'error';
        }
        if ($order->closed == 1) {  //重复回调
            return 'order close';
        }
        $service = new ShinePayService($order);
        if (!$service->checkSign($sign, $data, 'pay')) {
            return 'error';
        }
        $paid_at = Carbon::now()->timestamp;
        $state = $data['body']['status'] == 1 ? Order::SUCCESS : Order::FAILED;
        return (new Order())->rechargeComplete($state, $order->no, $data['body']['platformOrderId'], $paid_at, $input) ? 'success' : 'fail';
    }

    //异步回调
    public function withdrawalNotify(Request $request): string
    {
        try {
            $data = file_get_contents("php://input");
            $sign = $request->header('api-sign');
            $arr = json_decode($data, true);
            $order = Order::where('no', '=', $arr['body']['orderId'])->first();
            if (!$order) {
                return 'error';
            }
            $service = new ShinePayService($order);
            if (!$service->checkSign($sign, $arr, 'withdrawal')) {
                return 'error';
            }
            $order->payment_no = $arr['body']['platformOrderId'];
            $order->save();
            $res = (new Order())->withdrawComplete([
                'state' => $arr['body']['status'] == 1 ? Order::SUCCESS : Order::FAILED,
                'orderId' => $arr['body']['orderId'],
                'paidAt' => Carbon::now()->timestamp
            ], $arr);
            return $res ? 'success' : 'fail';
        } catch (\Exception $exception) {
            return 'error';
        }

    }
}
