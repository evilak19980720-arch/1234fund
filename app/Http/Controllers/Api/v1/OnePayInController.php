<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Pay\OnePayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class OnePayInController extends Controller
{

    public $password; //AES密钥

    public function __construct()
    {
        $this->password = config('services.onepay_in.aes_key');
    }

    //充值回调
    public function rechargeNotify(Request $request): string
    {
        try {
            $data = $request->all();
            //info($data);
            $data = $this->parseData($data);
            //info($data);
            if($data){
                $order = Order::query()->where('no', '=', $data['merchantNo'])->first();
                if (!$order || $order->closed == 1) {
                    return 'order error';
                }
                if($order->actual_amount == $data['amount']){
                    $paid_at = Carbon::now()->timestamp;
                    $state = $data['status'] == 2 ? Order::SUCCESS : Order::FAILED;
                    return (new Order())->rechargeComplete($state, $data['merchantNo'], '', $paid_at, json_encode($data, JSON_UNESCAPED_UNICODE)) ? 'success' : 'error';
                }
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
            $data = $request->all();
            //info($data);
            $arr = $this->parseData($data);
            //info($data);
            if($arr['status'] == 2 || $arr['status'] == 3){
                $order = Order::query()->where('no', '=', $arr['merchantNo'])->first();
                if (!$order) {
                    return 'order error';
                }
                $res = (new Order())->withdrawComplete([
                    'state' => $arr['status'] == 2 ? Order::SUCCESS : Order::FAILED,
                    'orderId' => $arr['merchantNo'],
                    'paidAt' => Carbon::now()->timestamp
                ], $arr);
                return $res ? 'success' : 'error';
            }
            return "error";
        } catch (Exception $exception) {
            return 'error';
        }


    }

    public $method = 'AES-128-CBC';

    /**解密
     * @param $aesSecret
     * @return false|string
     */
    public function decryptAes($aesSecret)
    {
        $str="";
        for($i=0;$i<strlen($aesSecret)-1;$i+=2){
            $str.=chr(hexdec($aesSecret[$i].$aesSecret[$i+1]));
        }
        $jsonData =  openssl_decrypt($str,$this->method,$this->password, OPENSSL_RAW_DATA,$this->password);
        $data = json_decode($jsonData,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        return $data;
    }

    /**解密并解析数据为数组；
     * @param string $data 示例:{"data":"50C7CC8B58CEFFD1A824ADE524F4F55DB0DAEE6029ADBB597C1F99D28E1D0779C33B562526F05C6821932DE20B6893ADD6834D3397B7A8E08CC03995A5CDEA7E6B4DF0485466D4C25AEB223DD456DBC0321921FDCA18F9596A1C14B54C5A018CC7C0B922E3DE371626887DA78E539DA81E64EC41938BC3EC5BEBC26A948803E8","merchantNo":"2022011116544301686"}
     * @return false|array
     */
    public function parseData( $data )
    {
        //$info = json_decode($data,true );
        $val = $this->decryptAes($data['data']);
        return $val;
    }



}
