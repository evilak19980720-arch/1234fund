<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Pay\LiuPayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class LiuPayController extends Controller
{
    //充值回调
    public function rechargeNotify(Request $request): string
    {
        try {
            $data = $request->all();
            info('liu收回调参数:'.json_encode($data));
            $order = Order::query()->where('no', '=', $data['customOrderId'])->first();
            if (!$order || $order->closed == 1) {
                return 'order error';
            }
            $service = new LiuPayService($order);
            if (!$service->checkSign($data, 'pay')) {
                return 'sign error';
            }
            if($order->amount != $data['realAmount']){  //金额不一致 直接返回
                return 'SUCCESS';
            }
            $paid_at = Carbon::now()->timestamp;
            $state = $data['status'] == 1 ? Order::SUCCESS : Order::FAILED;
            return (new Order())->rechargeComplete($state, $data['customOrderId'], '', $paid_at, json_encode($data, JSON_UNESCAPED_UNICODE)) ? 'SUCCESS' : 'error';
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
            info('liu付回调参数:'.json_encode($arr));
            $order = Order::query()->where('no', '=', $arr['remark'])->first();
            if (!$order) {
                return 'order error';
            }
            $service = new LiuPayService($order);
            if (!$service->checkSignWithdraw($arr, 'withdrawal')) {
                return 'sign error';
            }
            $arr['payment_no'] = $arr['withdrawId'];
            $res = (new Order())->withdrawComplete([
                'state' => $arr['status'] == 1 ? Order::SUCCESS : Order::FAILED,
                'orderId' => $arr['remark'],
                'paidAt' => Carbon::now()->timestamp
            ], $arr);
            return $res ? 'SUCCESS' : 'error';
        } catch (Exception $exception) {
            return 'error';
        }
    }
}
