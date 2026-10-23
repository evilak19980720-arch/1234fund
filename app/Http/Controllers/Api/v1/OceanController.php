<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Pay\OceanPayService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class OceanController extends Controller
{
    //充值回调
    public function rechargeNotify(Request $request): string
    {
        try {
            $data = $request->all();
            //$arr = json_decode($data, true);
            $arr = $data;
            $order = Order::query()->where('no', '=', $arr['merordercode'])->first();
            if (!$order) {
                return 'order error';
            }
            if ($order->closed == 1) {
                return 'order close';
            }
            $service = new OceanPayService($order);
            if (!$service->checkSign($arr, 'pay')) {
                return 'sign error';
            }
            $paid_at = Carbon::now()->timestamp;
            $state = $arr['returncode'] == '00' ? Order::SUCCESS : Order::FAILED;
            return (new Order())->rechargeComplete($state, $arr['merordercode'], '', $paid_at, json_encode($arr, JSON_UNESCAPED_UNICODE)) ? 'OK' : 'error';
        } catch (Exception $exception) {
            info('message:' . $exception->getMessage() . ',file:' . $exception->getFile() . ',line:' . $exception->getLine());
            return 'error';
        }
    }

    //提现回调
    public function withdrawalNotify(Request $request): string
    {
        try {
            //$data = file_get_contents("php://input");
            //$arr = json_decode($data, true);
            $data = $request->all();
            //$arr = json_decode($data, true);
            $arr = $data;
            $order = Order::query()->where('no', '=', $arr['merissuingcode'])->first();
            if (!$order) {
                return 'order error';
            }
            $service = new OceanPayService($order);
            if (!$service->checkSign_win($arr, 'withdrawal')) {
                return 'sign error';
            }
            $res = (new Order())->withdrawComplete([
                'state' => $arr['returncode'] == 'SUCCESS' ? Order::SUCCESS : Order::FAILED,
                'orderId' => $arr['merissuingcode'],
                'paidAt' => Carbon::now()->timestamp
            ], $arr);
            return $res ? 'OK' : 'error';
        } catch (Exception $exception) {
            return 'error';
        }
    }
}
