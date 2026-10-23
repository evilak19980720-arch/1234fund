<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Pay\EasyPayPayService;
use Carbon\Carbon;
use Exception;

class EasyPayController extends Controller
{
    //充值回调
    public function rechargeNotify(): string
    {
        try {
            $data = file_get_contents("php://input");
            $arr = json_decode($data, true);
            $order = Order::query()->where('no', '=', $arr['outTradeNo'])->first();
            if (!$order) {
                return 'order error';
            }
            if ($order->closed == 1) {
                return 'order close';
            }
            $service = new EasyPayPayService($order);
            if (!$service->checkSign($arr, 'pay')) {
                return 'sign error';
            }
            $paid_at = Carbon::now()->timestamp;
            $state = $arr['code'] == 1 ? Order::SUCCESS : Order::FAILED;
            return (new Order())->rechargeComplete($state, $arr['outTradeNo'], '', $paid_at, json_encode($arr, JSON_UNESCAPED_UNICODE)) ? 'OK' : 'error';
        } catch (Exception $exception) {
            info('message:' . $exception->getMessage() . ',file:' . $exception->getFile() . ',line:' . $exception->getLine());
            return 'error';
        }
    }

    //提现回调
    public function withdrawalNotify(): string
    {
        try {
            $data = file_get_contents("php://input");
            $arr = json_decode($data, true);

            $order = Order::query()->where('no', '=', $arr['outTradeNo'])->first();
            if (!$order) {
                return 'order error';
            }
            $service = new EasyPayPayService($order);
            if (!$service->checkSign($arr, 'withdrawal')) {
                return 'sign error';
            }
            $res = (new Order())->withdrawComplete([
                'state' => $arr['code'] == 1 ? Order::SUCCESS : Order::FAILED,
                'orderId' => $arr['outTradeNo'],
                'paidAt' => Carbon::now()->timestamp
            ], $arr);
            return $res ? 'OK' : 'error';
        } catch (Exception $exception) {
            return 'error';
        }
    }
}
