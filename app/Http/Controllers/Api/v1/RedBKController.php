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

class RedController extends Controller
{
    //提现到余额
    public function toBalance(Request $request)
    {
        $user = $request->user();

        $red = Red::where(['user_id' => $user->id, 'id' => $request->red_id])->first();
        if (!$red) {
            $this->errorResponse(Response::HTTP_OK, trans('error.50003'), 50003);
        }

        if ($red['amount'] < $red->red_withdrew_value) {
            $this->errorResponse(Response::HTTP_OK, trans('error.50004'), 50004);
        }

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

        /*
        $agent = Agent::getAgent($user->id);
        $red_ladder = RedLadder::where('level', $red->level)->where('admin_user_id', $agent)->first();
        //至少持有一条鱼苗才能提现
        if($red_ladder->pet_id){
            $pet_order = PetOrder::where(['user_id' => $user->id, 'pet_id' => $red_ladder->pet_id, 'state' => 0])->whereIn('type', [1])->first();
            $pet_name = Pet::where('id', $red_ladder->pet_id)->value('name');
            if (!$pet_order) {
                $this->errorResponse(Response::HTTP_OK, sprintf(trans('error.80002'), $pet_name), 80002);
            }
        }
        //当前用户下级至少持有一条鱼才能提现
        //$users = User::where('relation', 'like', '%' . $user->id . '%')->get(['id']);
        //修改为查询第一级下级
        if($red_ladder->subordinate_pet_id){
            $users = User::where('relation', 'regexp', ',' . $user->id . ',$')->get(['id']);
            $temp = null;
            foreach ($users as $item) {
                $pet_order = PetOrder::where(['user_id' => $item->id, 'pet_id' => $red_ladder->subordinate_pet_id, 'state' => 0])->whereIn('type', [1])->first();
                if ($pet_order) {
                    $temp = $pet_order;
                    break;
                }
            }
            if ($temp == null) {
                $pet_name = Pet::where('id', $red_ladder->subordinate_pet_id)->value('name');
                $this->errorResponse(Response::HTTP_OK, sprintf(trans('error.80003'), $pet_name), 80003);
            }
        }
        */

        //银行卡R
        $card = $user->cards()->first();
        if (!$card) {
            $this->errorResponse(Response::HTTP_OK, trans('error.40002'), 40002);
        }


        $red_withdrew_value = $red->red_withdrew_value;
        $res = null;

        $transaction_res = null;
        if ($red['amount'] >= $red_withdrew_value) {
            $transaction_res = DB::transaction(function () use ($red, $user, $red_withdrew_value) {
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
                    }
                    return $res;
                }
            });
        } else {
            $this->errorResponse(Response::HTTP_OK, trans('error.50004'), 50004);
        }
        $data['amount'] = $red['amount'];

        if ($transaction_res) {
            $res = app(Order::class)->createWithdrawOrder($user, $data['amount']);
            if ($res == 1) {
                return $this->success('The withdrawal application is successful, please wait patiently for the review');
            }
            return $this->fail($res, trans('error.' . $res));
        }
        $this->errorResponse(Response::HTTP_OK, trans('error.40003'), 40003);
    }

    //是否弹出红包
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
        } else {
            $data = $red->red_switch();
        }
        //初始红包金额
        $data['red_amount'] =  $this->receiveRedAmount(RedLadder::query()->where('id','=',1)->first());
        return $this->success($data);
    }

    //阶梯红包列表
    public function redLadder(Request $request)
    {
        $user = $request->user();
        $agent = Agent::getAgent($user->id);
        $red_ladders = RedLadder::query()->where('state', 1)->where('admin_user_id', $agent)->orderBy('level')->get(['id', 'level', 'red_withdrew_value', 'red_invite_num']);
        $reds = Red::query()->where(['user_id' => $user->id])->get(['id', 'level', 'state']);
        //加入用户红包完成状态
        $finish_count = 0;
        foreach ($red_ladders as &$red_ladder) {
            $red_ladder['user_finish_state'] = 0;
            foreach ($reds as $red) {
                if ($red_ladder->level == $red->level && ($red->state == 3 || $red->state == 4)) {
                    //已完成
                    $red_ladder['user_finish_state'] = 1;
                    $finish_count++;
                }
            }
            $red_ladder['confirm'] = false;
            $red_ladder['confirm_str'] = '';
        }
        if (count($red_ladders) > $finish_count) {
            $red_ladders[$finish_count]['user_finish_state'] = 2;
        }
        if (!$finish_count && !count($reds)) {
            $red_ladders = $red_ladders->map(function ($item) {
                if (in_array($item->level, [1, 2])) {
                    $item->user_finish_state = 2;
                }
                return $item;
            });
        } else {
            if (count($reds) == 1 && $reds->first()->level == 1) {
                $red_ladders = $red_ladders->map(function ($item) use ($reds) {
                    if ($item->level == 2) {
                        $item->user_finish_state = 2;
                        $item->confirm = in_array($reds->first()->state, [1, 2]);
                        $item->confirm_str = 'If you claim the task, the invitation task you are in progress will no longer be able to do';
                    }
                    return $item;
                });
            }
        }

        return $this->success($red_ladders);
    }

    //注册红包
    public function registerRed(Request $request)
    {
        $user = $request->user();
        $red_ladder = RedLadder::query()->where('admin_user_id', '=', $user->admin_user_id)->where('state', 1)->orderBy('level', 'asc')->first();
        if (!$red_ladder) {
            $this->errorResponse(Response::HTTP_OK, trans('error.50001'), 50001);
        }
        return $this->receiveRedPacket($user, $red_ladder);

    }

    //领红包
    public function store(RedRequest $request)
    {
        $user = $request->user();
        //$agent = Agent::getAgent($user->id);
        //if ($request->red_ladder_id == -1) {
            //$red_ladder = RedLadder::query()->where('admin_user_id', '=', $user->admin_user_id)->where('state', 1)->orderBy('level', 'asc')->first();
            $red_ladder = RedLadder::query()->where('id', '=', 1)->where('state', 1)->first();
        //}
        //else {
            //$red_ladder = RedLadder::query()->where('admin_user_id', '=', $agent)->find($request->red_ladder_id);
        //}
        if (!$red_ladder) {
            $this->errorResponse(Response::HTTP_OK, trans('error.50001'), 50001);
        }
        return $this->receiveRedPacket($user, $red_ladder);
    }

    //未登录领取红包
    public function noLoginRedStore($client, $device, $amount)
    {
        $red = Red::where(['client' => $client, 'device' => $device, 'user_id' => 0])->first(['user_id', 'amount', 'expires_at', 'created_at', 'updated_at', 'state']);
        if ($red) {
            $red->amount = $amount;
            $red->expires_at = strtotime("+" . Configuration::getItem('red_expires_time') . " hours");
            $red->save();
        } else {
            $red = Red::create([
                'client' => $client,
                'device' => $device,
                'user_id' => 0,
                'amount' => $amount,
                'expires_at' => strtotime("+" . Configuration::getItem('red_expires_time') . " hours"),
                'state' => 1
            ]);
        }
        return new RedResource($red);
    }

    public function getRandomData($value)
    {
        return [];
        return Cache::remember('withdraw_records:' . $value, KeysHandler::RED_LOG_EXPIRE, function () use ($value) {
            $home = new HomeController();
            $name = $home->name;
            shuffle($name);
            $name = array_slice($name, 0, 20);
            $red_withdrew_value = intval($value / 100);
            $arr = [];
            foreach ($name as $k => $v) {
                $arr[$k]['name'] = sprintf('%s***%s', mt_rand(6000, 9999), mt_rand(10, 99));
                $arr[$k]['avatar'] = rand(1, 12);
                $arr[$k]['datetime'] = date('d/m/Y');
                $arr[$k]['amount'] = $value;
                $arr[$k]['friends_count'] = mt_rand(1, 20);
                $arr[$k]['desc'] = "Invite " . mt_rand(1, 20) . " friends and get " . $red_withdrew_value . " rupees, have successfully withdrawn";
            }
            return $arr;
        });
    }

    // 打开红包
    public function update(RedRequest $request)
    {
        $red = (new Red())->get($request->id);
        if (!$red) {
            $this->errorResponse(Response::HTTP_OK, trans('error.50001'), 50001);
        }
        if ($red->closed != 0) {
            $this->errorResponse(Response::HTTP_OK, trans('error.50002'), 50002);
        }

        if ($red->open()) {
            $this->success();
        }
        $this->errorResponse(Response::HTTP_OK, trans('error.50003'), 50003);
    }


    //领红包流程
    public function receiveRedPacket(User $user, RedLadder $red_ladder ,$red_amount = 0)
    {
        //
        $red = Red::query()->where(['user_id' => $user->id, 'level' => $red_ladder->level])
            ->first(['id', 'user_id', 'amount', 'level', 'state', 'red_withdrew_value', 'invite_quantity']);
        if ($red) {
            //if ($red->state == 3) {
                //$this->errorResponse(Response::HTTP_OK, trans('error.50003'), 50003);
            //}
        } else {
            /*  该代码需要优化判断
            if ($red_ladder->level > 1) {
                $red2 = Red::where(['user_id' => $user->id, 'level' => $red_ladder->level - 1])->first();
                if (!$red2 || $red2->state != 3) {
                    $this->errorResponse(Response::HTTP_OK, trans('error.50006'), 50006);
                }
            }
            */
            //1.判断用户是否有正在进行中的红包
            if ($red_ladder->level > 2 && Red::query()->where('user_id', '=', $user->id)->where('state', '=', 1)->count()) {
                $this->errorResponse(Response::HTTP_OK, trans('error.50006'), 50006);
            }
            if ($red_ladder->level == 2) {
                //如果用户领取的是等级2的红包，判断用户是否领取过等级1的红包，如果领取了进行作废，如果未领取，则进行领取并作废
                if ($red = Red::query()->where('user_id', '=', $user->id)->where('level', '=', 1)->first()) {
                    $red->state = 4;
                    $red->save();
                } else {
                    $red_ladder_one = RedLadder::query()->where('level','=',1)->where('admin_user_id', '=', $red_ladder->admin_user_id)->first();
                    if ($red_ladder_one) {
                        $this->receiveRed($red_ladder_one,$user,4,$red_amount);
                    }
                }
            }
            //2.领取的红包等级是否符合,当前领取的红包等级必须大于 1，
            $red = Red::query()->where('user_id', '=', $user->id)->whereIn('state', [3, 4])->latest()->first();
            if ($red_ladder->level > 1 && (!$red || $red_ladder->level - 1 != $red->level)) {
                $this->errorResponse(Response::HTTP_OK, trans('error.50006'), 50006);
            }
            //获取红包初始金额
            $red = $this->receiveRed($red_ladder,$user,1,$red_amount);
        }
        $red['pet_id'] = $red_ladder->pet_id;
        $red['subordinate_pet_id'] = $red_ladder->subordinate_pet_id;
        $data['red'] = new RedResource($red);
        $red_log = Cache::remember('red_log:user_id:' . $user->id, KeysHandler::RED_LOG_EXPIRE, function () use ($user, $red_ladder) {
            return RedLog::with('users:id,name')->where(['user_id' => $user->id, 'level' => $red_ladder->level])
                ->orderBy('created_at', 'desc')
                ->get()->toArray();
        });
        foreach ($red_log as &$red){
            $red['users']['name'] = sprintf('%s****%s', substr($red['users']['name'], 0, 3), substr($red['users']['name'], -3));
        }
        $data['red_log'] = $red_log;

        //$AgentConf = new AgentConf();
        //$url = $AgentConf->getH5Url($user);
        //$data['red']['invite_url'] = $url . '/#' . $user->id;

        //$data['withdraw_record'] = $this->getRandomData($red->red_withdrew_value);
        return $this->success($data);
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
        $red_amount = ($red_withdrew_value - $amount) / 100;
        return Red::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'level' => $red_ladder->level,
            'state' => $state,
            'red_withdrew_value' => $red_withdrew_value,
            'invite_quantity' => 0,
            'red_distribution' => generateRedEnvelope($red_amount, $red_ladder->red_invite_num),
        ]);
    }


    protected function receiveRedAmount(RedLadder $red_ladder)
    {
        //获取红包初始金额
        if($red_ladder->red_value == 96000){
            return rand(960,990)*100;
        }
        $amount = $red_ladder->red_value;
        $red_withdrew_value = $red_ladder->red_withdrew_value;
        $diff = ($red_withdrew_value - $amount) * 0.1;
        //在红包初始金额上进行 -20%跟-10%  的随机减
        $amount = round(mt_rand($amount - $diff * 2, $amount - $diff) / 100) * 100;
        return $amount;
    }


}
