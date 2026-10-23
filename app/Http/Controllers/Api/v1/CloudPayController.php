<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Pay\CloudPayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Cache;

class CloudPayController extends Controller
{
    //充值回调
    public function rechargeNotify(Request $request): string
    {
        try {
            $data = $request->all();
            $order = Order::query()->where('no', '=', $data['order_id'])->first();
            if (!$order || $order->closed == 1) {
                return 'order error';
            }
            $service = new CloudPayService($order);
            if (!$service->checkSign($data, 'pay')) {
                return 'sign error';
            }
            $paid_at = Carbon::now()->timestamp;
            $state = $data['status'] == 5 ? Order::SUCCESS : Order::FAILED;
            return (new Order())->rechargeComplete($state, $data['order_id'], '', $paid_at, json_encode($data, JSON_UNESCAPED_UNICODE)) ? 'SUCCESS' : 'error';
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

            $key = "cloudorderid:".$arr['order_id'];
            if(Cache::has($key)){
                return 'order error';
            }
            Cache::add( $key, 1, Carbon::now()->addSeconds(5));
            $order = Order::query()->where('no', '=', $arr['order_id'])->first();
            if (!$order) {
                return 'order error';
            }
            $service = new CloudPayService($order);
            if (!$service->checkSign($arr, 'withdrawal')) {
                return 'sign error';
            }
            info('CLOUD提现回调进入：'.$arr['order_id']);
            $res = (new Order())->withdrawComplete([
                'state' => $arr['status'] == 5 ? Order::SUCCESS : Order::FAILED,
                'orderId' => $arr['order_id'],
                'paidAt' => Carbon::now()->timestamp
            ], $arr);
            return $res ? 'SUCCESS' : 'SUCCESS';
        } catch (Exception $exception) {
            return 'error';
        }
    }
}
