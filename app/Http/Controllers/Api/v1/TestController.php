<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\PayRequest;
use App\Jobs\AutoSynthesizeQueue;
use App\Jobs\SettleAccountsQueue;
use App\Models\Account;
use App\Models\Card;
use App\Models\Issue;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Pet;
use App\Models\PetOrder;
use App\Models\User;
use App\Models\Vip;
use App\Services\FundingService;
use Hedeqiang\UMeng\Android;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Jobs\InterestQueue;
use App\Models\Atlas;


class TestController extends Controller
{
    //恢复数据
    public function recoverData()
    {
        /*
        $orders = Order::where('type', 0)->where('id', '<', 7790)->get();
        foreach ($orders as $order) {
            $account = new Account();
            $account->timestamps = false;
            $account->order_id = $order->id;
            $account->type = 0;
            $account->amount = -$order->amount;
            $account->from = $order->user_id;
            $account->created_at = $order->created_at;
            $account->updated_at = $order->updated_at;
            $account->save();
        }
        */
    }

    public function modelTest()
    {
        $pet_up = Pet::where('level', 2)->first();
        echo $pet_up->stock_quantity . "\n";
        $pet_up->increment('stock_quantity', 10);
        echo $pet_up->stock_quantity . "\n";
        $pet_up->save();
        var_dump($pet_up->stock_quantity);
    }

    //更新未结算的订单表
    public function batchUpdatePetOrder()
    {
        $pet_orders = PetOrder::whereRaw('datediff(curdate() ,`start_at`)<=`day`')->where('type', '<>', '3')->orderBy('id', 'desc')->get();
//        return $pet_orders;
        foreach ($pet_orders as $pet_order) {
            $pet = Pet::find($pet_order->pet_id);
            if ($pet) {
                PetOrder::where('id', $pet_order->id)->update([
                    'price' => $pet->buying_price,
                    'profit_amount' => $pet->profit * $pet_order->number,
                    'selling_price' => $pet->selling_price,
                    'selling_amount' => $pet->selling_price * $pet_order->number
                ]);
            }
        }
    }

    //自动卖出测试
    public function queueSettleTest()
    {
        //查询已购买的并且未出售的 type=1 , state=0
        $pet_orders = DB::table('pet_orders')
            ->whereRaw('datediff(curdate() ,`start_at`)>=`day` and type=1 and state=0')
            ->get(['id']);
        if ($pet_orders->isNotEmpty()) {
            foreach ($pet_orders as $pet_order) {
                SettleAccountsQueue::dispatch($pet_order)->onQueue(env('REDIS_QUEUE', 'default'));
            }
        }
    }

    public function queueAutoTest()
    {
        $pets = DB::table('pets')->whereRaw('waiting_quantity > 1 and is_auto=1')->get(['id']);
//        return $pets;
        if ($pets->isNotEmpty()) {
            foreach ($pets as $pet) {
                AutoSynthesizeQueue::dispatch($pet);
            }
        }
    }

    public function queueTest()
    {
        //查询已购买的并且未出售的 type=1 , state=0
        $pet_orders = DB::table('pet_orders')
            ->whereRaw('datediff(curdate() ,`start_at`)>=`day` and type=1 and state=0')
            ->get(['id']);
//        return $pet_orders;

        if ($pet_orders->isNotEmpty()) {
            foreach ($pet_orders as $pet_order) {
                SettleAccountsQueue::dispatch($pet_order);
            }
        }
    }

    public function foo(Request $request)
    {
        $issue = Issue::find(2);
        $service = new FundingService($issue);
        return $service->getPlayer()->draw();

        $vips = Vip::getVip();
        foreach ($vips as $vip) {
            $result[] = [
                'id' => $vip->id,
                'completed' => 0,
                'name' => $vip->name,
                'total' => $vip->quantity
            ];
        }
        Cache::put('xxoo', $result);
//        Redis::hmset('xxoo',$data);
        return 'a';
//        $ttl = Redis::ttl('aaa');
//        Redis::expire('user', $ttl +50);
//        return $ttl;

        return $this->errorResponse(Response::HTTP_OK, '您还没有通过认证', 1003);
        dd(config('app.debug'));
        Redis::zadd('arr', 1, 1);
        Redis::zadd('arr', 2, 2);
        dd(Redis::zscore('arr', 2));
//        Redis::Sadd('arr','apple');
//        dd(Redis::sismember('brr',100003));
//        Cache::put('name', 'share');
//        return $this->success(Cache::get('name'));
    }

    public function bar()
    {
        $postArr = array(
            'account' => env('SMS_253_ACCOUNT'),
            'password' => env('SMS_253_PASSWORD'),
            'msg' => '1234 is your verification code, please verify the identity. please do not share this OTP. [TIG]。',
            'mobile' => 959457933855
        );

        return $this->curlPost('http://intapi.253.com/send/json', $postArr);
    }

    private function curlPost($url, $postFields)
    {
        $postFields = json_encode($postFields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8'
            )
        );

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $ret = curl_exec($ch);

        if (false == $ret) {
            $result = curl_error($ch);
        } else {
            $rsp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $rsp) {
                $result = "请求状态 " . $rsp . " " . curl_error($ch);
            } else {
                $result = $ret;
            }
        }
        curl_close($ch);

        return $result;
    }

    public function selectIp(Request $request)
    {

        print_r($request);
        exit;

        /*      print_r(Carbon::today()->startOfWeek());
                exit;
        */
        $interests = DB::table('interests')->select('user_id')->get();
        foreach ($interests as $val) {
            InterestQueue::dispatch($val->user_id);
        }

        exit;
        $card = DB::table('cards')->where('id', '=', 1)->first();
        return $card->number;
        return $this->success($card);
    }


    public function recharge(PayRequest $request)
    {

    }


    /**
     * 生成机器人会员
     * 层级ID4
     * */
    public function createUsers(Request $request){
        for ($i = 1; $i <= 100; $i++) {
            $phone = mt_rand(601, 998) . '0000' . mt_rand(101, 999);
            $users[] = [
                'phone' => $phone,
                'name' => $phone,
                'password' => Hash::make('sr4S6s487'),
                'avatar' => mt_rand(1, 12),
                'hierarchy_id' => 4,
                'buy_rebate' => 0,
                'hidephone' => 1,
                'created_at' => Carbon::create(2021, 1, 1, 00, 00, 00)->toDateTimeString(),
                'updated_at' => Carbon::create(2021, 1, 1, 00, 00, 00)->toDateTimeString(),
            ];
        }
        DB::table('users')->insert($users);
    }

    /**
     * 代理数据平移
     * */
    public function py(){
        // 1宠物 2图集
        /*
        $pets = Pet::where('admin_user_id','=',0)->get();
        foreach ($pets as $pet){
            $pet = $pet->toArray();
            $atlas = Atlas::where('pet_id','=',$pet['id'])->first();
            unset($pet['id']);
            $pet['admin_user_id'] = 20;
            $id = Pet::insertGetId($pet);
            $atl = [];
            $atl['pet_id'] = $id;
            $atl['title'] = $atlas->title;
            $atl['img'] = json_encode($atlas->img);
            Atlas::insert($atl);
        }
        */
    }






}
