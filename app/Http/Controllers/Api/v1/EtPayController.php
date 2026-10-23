<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Pay\EtPayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class EtPayController extends Controller
{
    //充值回调
    public function rechargeNotify(Request $request): string
    {
        try {
            $data = $request->all();
            info($data);
            $order = Order::query()->where('no', '=', $data['orderno'])->first();
            if (!$order || $order->closed == 1) {
                return 'order error';
            }
            $service = new EtPayService($order);
            if (!$service->checkSign($data, 'pay')) {
                return 'sign error';
            }
            if($order->actual_amount == $data['price'] * 100){
                $paid_at = Carbon::now()->timestamp;
                $state = $data['status'] == 1 ? Order::SUCCESS : Order::FAILED;
                return (new Order())->rechargeComplete($state, $data['orderno'], '', $paid_at, json_encode($data, JSON_UNESCAPED_UNICODE)) ? 'success' : 'error';
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
            info($arr);
            if($arr['status'] == 1 || $arr['status'] == 3 || $arr['status'] == 2){ //1已支付，2已退款,3失败
                $order = Order::query()->where('no', '=', $arr['orderno'])->first();
                if (!$order) {
                    return 'order error';
                }
                $service = new EtPayService($order);
                if (!$service->checkSign($arr, 'withdrawal')) {
                    return 'sign error';
                }
                $res = (new Order())->withdrawComplete([
                    'state' => $arr['status'] == 1 ? Order::SUCCESS : Order::FAILED,
                    'orderId' => $arr['orderno'],
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
