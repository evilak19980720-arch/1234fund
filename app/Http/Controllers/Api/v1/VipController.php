<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\VipRequest;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Rule;
use App\Models\Task;
use App\Models\User;
use App\Models\Vip;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * 会员等级
 * @package App\Http\Controllers\Api
 */
class VipController extends Controller
{
    public function index(Request $request)
    {
        $list = Vip::getVip();
        foreach ($list as &$val){
            if($val->discount == 1){
                $val->discount = '0%';
            }else{
                $val->discount = $val->discount * 100 . "%";
            }
            //假如你有10个下属充值投资，你收益是： 10BDT
            $val->viptext = sprintf(transtable('viptext'), $val->subordinate_buy, $val->send_amount/100 . ' '.Configuration::getConfig(null,'currency_symbol'));

        }
        return $this->success([
            'list' => $list,
            //购买了的下级数量
            //'subordinate_buy' => app(User::class)->subordinateBuy($request->user()),
            'subordinate_buy' => 0,  //不用的字段,写死
            'vip' => 2  //不用的字段,写死
        ]);
    }

    public function typeVip()
    {
        $data = [];
        $vips = Vip::getVip() ?: [];
        if ($vips) {
            foreach ($vips as $vip) {
                array_push($data, [
                    'id' => $vip->id,
                    'text' => $vip->name
                ]);
            }
        }
        return $data;
    }

    public function store(VipRequest $request)
    {
        $user = $request->user();

        // 检查支付密码
//        if (!Hash::check($request->password, $user->payment_password)) {
//            $this->errorResponse(Response::HTTP_OK,trans('error.10008'),10008);
//        }
        // 检查余额
        $vip = Vip::getVip($request->vip);
        if ($user->balance < $vip->discount_price) {
            $this->errorResponse(Response::HTTP_OK, transtable('10009'), 10009);
        }
        // 检查等级
        $userVip = Vip::getVip($user->vip_id);
        if ($userVip->level >= $vip->level) {
            $this->errorResponse(Response::HTTP_OK, transtable('10010'), 10010);
        }

        $res = DB::transaction(function () use ($vip, $user, $userVip) {
            // 余额扣减
            $res = $user->decrement('balance', $vip->discount_price);
            Account::create([
                'type' => Account::VIP,
                'to' => $user->id,
                'from' => $user->id,
                'amount' => -1 * $vip->discount_price,
                'level' => 0
            ]);
            // 等级更改
            $user->vip_id = $vip->id;
            $isVip = $userVip->level != 0 ? true : false;
            if ($isVip) {
                $user->rebate = 0;
            } else {
                $user->rebate = 1;
            }
            $user->save();

            // 营销充值盈利
            if (!$isVip) {
                return $user;
            }
            $user_ids = false;
            if (!empty($user->relation)) {
                $user_ids = explode(',', trim($user->relation, ','));
            }
            if (!$user_ids) {
                return $res;
            }

            $rules = Rule::getRechargeRule();
            $vips = User::whereIn('id', $user_ids)->select('vip_id')->get()->toArray();
            $vips = array_column($vips, 'vip_id');

            while (count($user_ids)) {
                $uid = array_pop($user_ids);
                $rule = array_shift($rules);
                $vid = array_pop($vips);

                $v = Vip::getVip($vid);
                if ($vip->level > $v->level) {
                    $price = $v->discount_price;
                } elseif ($vip->level < $v->level) {
                    $price = $vip->discount_price;
                } else {
                    $price = $vip->discount_price;
                }
                $money = $price * ($rule['percent'] / 100);
                if ($money <= 0) {
                    continue;
                }
                User::where('id', '=', $uid)->increment('balance', $money);    // 加钱
                Account::create([
                    'type' => Account::RIncome,
                    'to' => $uid,
                    'from' => $user->id,
                    'amount' => $money,
                    'level' => $rule['level']
                ]);
            }
            return $res;
        });

        return $this->success();
    }
}
