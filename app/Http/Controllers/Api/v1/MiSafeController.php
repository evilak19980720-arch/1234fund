<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Pay\MiSafePayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class MiSafeController extends Controller
{
    //充值回调
    public function rechargeNotify(Request $request): string
    {
        try {
            $arr = $request->all();
            $order = Order::query()->where('no', '=', $arr['orderNo'])->first();
            if (!$order) {
                return 'order error';
            }
            if ($order->closed == 1) {
                return 'order close';
            }
            $service = new MiSafePayService($order);
            if (!$service->checkSign($arr, 'pay')) {
                return 'sign error';
            }
            $paid_at = Carbon::now()->timestamp;
            $state = $arr['payResult'] == 1 ? Order::SUCCESS : Order::FAILED;
            return (new Order())->rechargeComplete($state, $arr['orderNo'], $arr['ptOrderNo'] ?? '', $paid_at, json_encode($arr, JSON_UNESCAPED_UNICODE)) ? 'SUCCESS' : 'FAIL';
        } catch (Exception $exception) {
            info('miSafe代收异步message:' . $exception->getMessage() . ',file:' . $exception->getFile() . ',line:' . $exception->getLine());
            return 'FAIL';
        }
    }

    //提现回调
    public function withdrawalNotify(Request $request): string
    {
        try {
            $arr = $request->all();

            $order = Order::query()->where('no', '=', $arr['orderNo'])->first();
            if (!$order) {
                return 'order error';
            }
            $service = new MiSafePayService($order);
            if (!$service->checkSign($arr, 'withdrawal')) {
                return 'sign error';
            }
            $res = (new Order())->withdrawComplete([
                'state' => $arr['payResult'] == 1 ? Order::SUCCESS : Order::FAILED,
                'orderId' => $arr['orderNo'],
                'paidAt' => Carbon::now()->timestamp
            ], $arr);
            return $res ? 'SUCCESS' : 'FAIL';
        } catch (Exception $exception) {
            info('miSafe代付异步message:' . $exception->getMessage() . ',file:' . $exception->getFile() . ',line:' . $exception->getLine());
            return 'FAIL';
        }
    }
}
