<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\ServiceS\Pay\PayPlusPayService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class PayPlusController extends Controller
{
    //充值回调
    public function rechargeNotify(Request $request): string
    {
        try {
            $data = $request->all();
            $order = Order::query()->where('no', '=', $data['orderId'])->first();
            if (!$order) {
                return 'success';
            }
            if ($order->closed == 1) {
                return 'success';
            }
            $service = new PayPlusPayService($order);
            if (!$service->checkSign($data, 'pay')) {
                return 'success';
            }
            $paid_at = Carbon::now()->timestamp;
            $state = $data['status'] == '1' ? Order::SUCCESS : Order::FAILED;
            return (new Order())->rechargeComplete($state, $data['orderId'], '', $paid_at, json_encode($data, JSON_UNESCAPED_UNICODE)) ? 'success' : 'error';
        } catch (Exception $exception) {
            return 'success';
        }
    }

    //提现回调
    public function withdrawalNotify(Request $request): string
    {
        try {
            $data = $request->all();
            $order = Order::query()->where('no', '=', $data['orderId'])->first();
            if (!$order) {
                return 'success';
            }
            $service = new PayPlusPayService($order);
            if (!$service->checkSign($data, 'withdrawal')) {
                return 'success';
            }
            $res = (new Order())->withdrawComplete([
                'state' => $data['status'] == '1' ? Order::SUCCESS : Order::FAILED,
                'orderId' => $data['orderId'],
                'paidAt' => Carbon::now()->timestamp
            ], $data);
            return $res ? 'success' : 'error';
        } catch (Exception $exception) {
            return 'error';
        }
    }
}
