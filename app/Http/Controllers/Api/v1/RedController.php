<?php

namespace App\Http\Controllers\Api\v1;

use App\Handlers\KeysHandler;
use App\Http\Requests\Api\v1\RedRequest;
use App\Http\Resources\RedResource;
use App\Models\Account;
use App\Models\Agent;
use App\Models\AgentConf;
use App\Models\Configuration;
use App\Models\Order;
use App\Models\Pet;
use App\Models\PetOrder;
use App\Models\Red;
use App\Models\RedLadder;
use App\Models\RedLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Phone;

class RedController extends Controller
{
    //提现到余额
    public function toBalance(Request $request)
    {
        $user = $request->user();
        $red = Red::where(['user_id' => $user->id, 'state' => 2])->first();
        if (!$red) {
            $this->errorResponse(Response::HTTP_OK, transtable(50003), 50003);
        }
        if ($red['amount'] < $red->red_withdrew_value) {
            $this->errorResponse(Response::HTTP_OK, transtable(50004), 50004);
        }

        /*
        //新增自己购买任意
        $my_order = PetOrder::query()->where('user_id', $user->id)
            ->whereIn('type', [1, 3])->groupBy('user_id')->get()->count();
        if($my_order < 1){
            $this->errorResponse(Response::HTTP_OK, trans('error.80002'), 80002);
        }
        $child = User::query()
            ->where('relation', 'regexp', sprintf(',%d,%s$', $user->id, str_repeat('[0-9]+,', 0)))
            ->get();
        $pet_order = PetOrder::query()->whereIn('user_id', $child->pluck('id'))
            ->where('pet_id', '>', 72)
            ->whereIn('type', [1, 3])->groupBy('user_id')->get()->count();
        if($pet_order < 2){
            $this->errorResponse(Response::HTTP_OK, trans('error.80003'), 80003);
        }
        */

        //银行卡R
        $card = $user->cards()->first();
        if (!$card) {
            $this->errorResponse(Response::HTTP_OK, transtable('40002'), 40002);
        }

        $agent = Agent::getAgent($user->id);
        $red_ladder = RedLadder::where('level', $red->level)->first();
        $currency_symbol = Configuration::getItem('currency_symbol');
        //当前用户下级至少持有一条鱼才能提现
        //$users = User::where('relation', 'like', '%' . $user->id . '%')->get(['id']);
        //修改为查询第一级下级
        if($red_ladder->subordinate_pet_id){
            $child = User::query()
                ->where('relation', 'regexp', ',' . $user->id . ',$')
                ->get();
            $pet_order = PetOrder::query()->whereIn('user_id', $child->pluck('id'))
                ->where('type', '=',1)
                ->where('pet_id','=',$red_ladder->subordinate_pet_id)
                ->groupBy('user_id')->get()->count();
            if($pet_order < $red_ladder->subordinate_num || $user->sub_first_petorder < $red_ladder->subordinate_num){
                $pet_buying_price = Pet::where('id', $red_ladder->subordinate_pet_id)->value('buying_price');
                $price_str = $pet_buying_price/100 .$currency_symbol;
                info('会员领取红包奖励失败,目前:'.$pet_order);
                $this->errorResponse(Response::HTTP_OK, sprintf(transtable('80003'),$red_ladder->subordinate_num ,$price_str), 80003);
            }




            /*
            $users = User::where('relation', 'regexp', ',' . $user->id . ',$')->get(['id']);
            $temp = null;
            foreach ($users as $item) {
                $pet_order = PetOrder::where(['user_id' => $item->id, 'pet_id' => $red_ladder->subordinate_pet_id, 'state' => 0])->whereIn('type', [1,3])->first();
                if ($pet_order) {
                    $temp = $pet_order;
                    break;
                }
            }
            if ($temp == null) {
                $pet_buying_price = Pet::where('id', $red_ladder->subordinate_pet_id)->value('buying_price');
                $price_str = $pet_buying_price/100 .$currency_symbol;
                $this->errorResponse(Response::HTTP_OK, sprintf(transtable('80003'),$red_ladder->subordinate_num ,$price_str), 80003);
            }
            */


        }


        //1：您需要充值并投资%s的产品才能进行下一步
        //2：您必须有%d个下级充值并投资%s的产品才能提现


        //至少持有一条鱼苗才能提现
        if($red_ladder->pet_id){
            $pet_order = PetOrder::where(['user_id' => $user->id, 'pet_id' => $red_ladder->pet_id, 'state' => 0])->whereIn('type', [1,3])->first();
            $pet_buying_price = Pet::where('id', $red_ladder->pet_id)->value('buying_price');
            $price_str = $pet_buying_price/100 .$currency_symbol;
            if (!$pet_order) {
                $this->errorResponse(Response::HTTP_OK, sprintf(transtable(80002), $price_str), 80002);
            }
        }



        $red_withdrew_value = $red->red_withdrew_value;
        $res = null;

        $transaction_res = null;
        if ($red['amount'] >= $red_withdrew_value) {
            $transaction_res = DB::transaction(function () use ($red, $user, $red_withdrew_value, $red_ladder) {
                $red = Red::where(['user_id' => $user->id, 'state' => 2])->lockForUpdate()->first();
                if ($red) {
                    $res = Account::create([
                        'type' => Account::INVITE_INCOME,
                        'to' => $user->id,
                        'from' => 0,
                        'amount' => $red['amount'],
                        'level' => 0
                    ]);
                    if ($res) {
                        User::where('id', '=', $user->id)->increment('balance', $red['amount']);
                        $red->state = 3;
                        $red->save();

                        //扣下级首次购买数量
                        $user->decrement('sub_first_petorder', $red_ladder->subordinate_num);

                    }
                    return $res;
                }
            });
        } else {
            $this->errorResponse(Response::HTTP_OK, transtable('50004'), 50004);
        }
        $data['amount'] = $red['amount'];

        if ($transaction_res) {
            //$res = app(Order::class)->createWithdrawOrder($user, $data['amount']);
            //if ($res == 1) {

                //提现成功 领取新红包
                app(RedController::class)->receiveRedPacket($user,0);

                return $this->success('');
            //}
            //return $this->fail($res, transtable($res));
        }
        $this->errorResponse(Response::HTTP_OK, transtable('40003'), 40003);
    }

    //是否弹出红包 红包开关
    public function redSwitch(Request $request)
    {
        $user = null;
        $authorization = $request->header('authorization');
        $red = new Red();
        if ($authorization) {
            $user = JWTAuth::parseToken()->touser();
        }
        if ($user) {
            $data = $red->red_switch($user->id);
            $data['now_count'] = Red::query()->where(['user_id' => $user->id])->whereIn('state',[1,2,3])->count();
            $data['red_amount'] =  0;
            $data['red_withdrew_value'] =  0;
            $data['expires_at'] =  0;
        } else {
            $data = $red->red_switch();
            $data['now_count'] = 0;
            //初始红包金额
            $red_ladder = RedLadder::query()->where('state','=',1)->orderBy('level')->first();
            $data['red_amount'] =  $this->receiveRedAmount($red_ladder);
            $data['red_withdrew_value'] =  $red_ladder->red_withdrew_value;
            $data['expires_at'] = strtotime("+" . Configuration::getItem('red_expires_time') . " hours");
        }
        //总次数
        $data['all_count'] = RedLadder::query()->where('state','=',1)->count();
        return $this->success($data);
    }

    //领红包
    public function store(RedRequest $request)
    {
        $user = $request->user();
        return $this->receiveRedPacket($user);
    }

    //红包详情
    public function detail(Request $request)
    {
        $user = $request->user();
        $red = Red::query()->where('user_id','=',$user->id)->whereIn('state',[1,2])
            ->first(['id', 'user_id', 'amount', 'state', 'red_withdrew_value', 'invite_quantity','expires_at','level']);
        if (!$red) {
            app(RedController::class)->receiveRedPacket($user,0);
            $red = Red::query()->where('user_id','=',$user->id)->whereIn('state',[1,2])
                ->first(['id', 'user_id', 'amount', 'state', 'red_withdrew_value', 'invite_quantity','expires_at','level']);
            //return $this->fail(50001, transtable('50001'));
        }

        $red_ladder = RedLadder::query()->where('level','=',$red->level)->first();
        $red->invitation_copy = transtableadmin('red_ladders.invitation_copy.'.$red_ladder->id);

        $data['red'] = new RedResource($red);

        $red_log = Cache::remember('red_log:red_id:' . $red->id, KeysHandler::RED_LOG_EXPIRE, function () use ($user, $red) {
            return RedLog::with('users:id,name')->where(['user_id' => $user->id, 'red_id' => $red->id])
                ->orderBy('created_at', 'desc')
                ->get()->toArray();
        });
        foreach ($red_log as &$red){
            $red['users']['name'] = sprintf('%s****%s', substr($red['users']['name'], 0, 3), substr($red['users']['name'], -3));
        }
        $data['red_log'] = $red_log;

        return $this->success($data);
    }

    //领红包流程
    public function receiveRedPacket(User $user ,$red_amount = 0)
    {
        //有进行中的
        if(Red::query()->where(['user_id' => $user->id])->whereIn('state',[1,2])->exists()){
            return $this->fail(50002, transtable('50002'));
        }
        $nowredcount = Red::query()->where(['user_id' => $user->id])->where('state','=',3)->count();
        $red_ladder = RedLadder::query()->where('state','=',1)->where('level','=',$nowredcount+1)->orderBy('level')->first();
        if(!$red_ladder){
            return $this->fail(50001, transtable('50001'));
        }
        $this->receiveRed( $red_ladder, $user,1,$red_amount);
        return $this->success();
    }

    protected function receiveRed(RedLadder $red_ladder,User $user,int $state = 1,$red_amount = 0): Red
    {
        //获取红包初始金额
        //$amount = $red_ladder->red_value;
        $red_withdrew_value = $red_ladder->red_withdrew_value;
        // $diff = ($red_withdrew_value - $amount) * 0.1;
        //在红包初始金额上进行 -20%跟-10%  的随机减
        //$amount = round(mt_rand($amount - $diff * 2, $amount - $diff) / 100) * 100;
        //计算满额所需金额
        if($red_amount > 0){
            $amount = $red_amount;
        }else{
            $amount = $this->receiveRedAmount($red_ladder);
        }
        //$red_amount = ($red_withdrew_value - $amount) / 100;
        $red_amount = $red_withdrew_value - $amount;
        return Red::create([
            'level' => $red_ladder->level,
            'user_id' => $user->id,
            'amount' => $amount,
            'state' => $state,
            'expires_at' => strtotime("+" . Configuration::getItem('red_expires_time') . " hours"),
            'red_withdrew_value' => $red_withdrew_value,
            'invite_quantity' => 0,
            'red_distribution' => generateRedEnvelope($red_amount, $red_ladder->red_invite_num),
        ]);
    }

    protected function receiveRedAmount(RedLadder $red_ladder)
    {
        //获取红包初始金额
        //if($red_ladder->red_value == 96000){
        //    return rand(960,990)*100;
        //}
        $amount = $red_ladder->red_value;
        $red_withdrew_value = $red_ladder->red_withdrew_value;
        $diff = ($red_withdrew_value - $amount) * 0.1;
        //在红包初始金额上进行 -20%跟-10%  的随机减
        //$amount = round(mt_rand($amount - $diff * 2, $amount - $diff) / 100) * 100;
        $amount = mt_rand($amount - $diff * 2, $amount - $diff);
        return $amount;
    }



    //生成收益记录
    public function profitRecord()
    {
        $logs = Cache::remember('profit_record', KeysHandler::RED_LOG_EXPIRE, function () {
            $red_ladder = RedLadder::query()->where('state','=',1)->pluck('red_withdrew_value')->toArray();
            $arr = [];
            for ($i = 0;$i<20;$i++) {
                $data['name'] = sprintf('%s***%s', mt_rand(901, 999), mt_rand(100, 999));
                $data['red'] = $red_ladder[array_rand($red_ladder)];
                $arr[] = $data;
            }
            return array_values($arr);
        });
        return $this->success($logs);
    }


    //邀请文案
    public function inviteText(Request $request){
        //$phones = Phone::query()->where('state','=',0)->inRandomOrder()->limit(10)->pluck('phone');
        /*
        $phones = User::query()->whereDoesntHave('orders', function ($query) {
            $query->where('type', '=', '1');
        })->inRandomOrder()->limit(10)->pluck('phone');
        */
        $phones = User::query()->where('balance','=',0)->where('frozen','=',0)->inRandomOrder()->limit(10)->pluck('phone');
        $data['whatsapptext_send_phone'] = $phones;
        return $this->success($data);
    }


    //发送
    public function sendText(Request $request)
    {
        $phone = $request->phone;
        Phone::query()->where('phone','=',$phone)->update([
            'state' => 1,
            'send_at' => Carbon::now()
        ]);
        return $this->success();
    }


}
