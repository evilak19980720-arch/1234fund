<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Pay\HxPayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class HxPayController extends Controller
{
    //充值回调
    public function rechargeNotify(Request $request): string
    {
        try {
            $data = $request->all();
            $order = Order::query()->where('no', '=', $data['merchantCode'])->first();
            if (!$order || $order->closed == 1) {
                return 'order error';
            }
            $service = new HxPayService($order);
            if (!$service->checkSign($data, 'pay')) {
                return 'sign error';
            }

            if($data['status'] == 'PENDING'){
                return 'success';
            }
            $paid_at = Carbon::now()->timestamp;
            $state = $data['status'] == 'SUCCESS' ? Order::SUCCESS : Order::FAILED;
            return (new Order())->rechargeComplete($state, $data['merchantCode'], '', $paid_at, json_encode($data, JSON_UNESCAPED_UNICODE)) ? 'success' : 'error';
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
            $order = Order::query()->where('no', '=', $arr['merchantCode'])->first();
            if (!$order) {
                return 'order error';
            }
            $service = new HxPayService($order);
            if (!$service->checkSign($arr, 'withdrawal')) {
                return 'sign error';
            }
            if($arr['status'] == 'PENDING'){
                return 'success';
            }
            $res = (new Order())->withdrawComplete([
                'state' => $arr['status'] == 'SUCCESS' ? Order::SUCCESS : Order::FAILED,
                'orderId' => $arr['merchantCode'],
                'paidAt' => Carbon::now()->timestamp
            ], $arr);
            return $res ? 'success' : 'error';
        } catch (Exception $exception) {
            return 'error';
        }
    }
}
