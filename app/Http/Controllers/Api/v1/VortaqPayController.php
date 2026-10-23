<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Pay\VortaqPayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class VortaqPayController extends Controller
{
    //充值回调
    public function rechargeNotify(Request $request): string
    {
        try {
            $data = $request->all();
            //info($data);
            $order = Order::query()->where('no', '=', $data['order_id'])->first();
            if (!$order || $order->closed == 1) {
                return 'order error';
            }
            $service = new VortaqPayService($order);
            if (!$service->checkSign($data, 'pay')) {
                return 'sign error';
            }
            if($order->actual_amount == $data['amount'] * 100){
                $paid_at = Carbon::now()->timestamp;
                $state = $data['status_code'] == 100 ? Order::SUCCESS : Order::FAILED;
                return (new Order())->rechargeComplete($state, $data['order_id'], '', $paid_at, json_encode($data, JSON_UNESCAPED_UNICODE)) ? 'SUCCESS' : 'error';
            }
            return 'error';
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
            if($arr['status_code'] == 100 || $arr['status_code'] == 4  || $arr['status_code'] == 5){
                $order = Order::query()->where('no', '=', $arr['order_id'])->first();
                if (!$order) {
                    return 'order error';
                }
                $service = new VortaqPayService($order);
                if (!$service->checkSign($arr, 'withdrawal')) {
                    return 'sign error';
                }
                $res = (new Order())->withdrawComplete([
                    'state' => $arr['status_code'] == 100 ? Order::SUCCESS : Order::FAILED,
                    'orderId' => $arr['order_id'],
                    'paidAt' => Carbon::now()->timestamp
                ], $arr);
                return $res ? 'success' : 'error';
            }
            return "error";
        } catch (Exception $exception) {
            return 'error';
        }


    }
}
