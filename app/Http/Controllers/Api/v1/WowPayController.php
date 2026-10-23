<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Pay\WowPayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class WowPayController extends Controller
{
    //充值回调
    public function rechargeNotify(Request $request): string
    {
        try {
            $data = $request->all();
            $order = Order::query()->where('no', '=', $data['mchOrderNo'])->first();
            if (!$order || $order->closed == 1) {
                return 'order error';
            }
            $service = new WowPayService($order);
            if (!$service->checkSign($data, 'pay')) {
                return 'sign error';
            }
            $paid_at = Carbon::now()->timestamp;
            $state = $data['tradeResult'] == 1 ? Order::SUCCESS : Order::FAILED;
            return (new Order())->rechargeComplete($state, $data['mchOrderNo'], '', $paid_at, json_encode($data, JSON_UNESCAPED_UNICODE)) ? 'success' : 'error';
        } catch (Exception $exception) {
            info('message:' . $exception->getMessage() . ',file:' . $exception->getFile() . ',line:' . $exception->getLine());
            return 'error';
        }
    }

    //提现回调
    public function withdrawalNotify(Request $request): string
    {
        try {
            $arr = $request->all();
            $order = Order::query()->where('no', '=', $arr['merTransferId'])->first();
            if (!$order) {
                return 'order error';
            }
            $service = new WowPayService($order);
            if (!$service->checkSign($arr, 'withdrawal')) {
                return 'sign error';
            }
            $res = (new Order())->withdrawComplete([
                'state' => $arr['tradeResult'] == 1 ? Order::SUCCESS : Order::FAILED,
                'orderId' => $arr['merTransferId'],
                'paidAt' => Carbon::now()->timestamp
            ], $arr);
            return $res ? 'success' : 'error';
        } catch (Exception $exception) {
            return 'error';
        }
    }
}
