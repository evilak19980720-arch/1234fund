<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\User;
use App\Models\Vip;
use Illuminate\Http\Request;
use App\Http\Requests\Api\v1\InterestRechargeRequest;
use App\Http\Requests\Api\v1\InterestWithdrawalRequest;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Interest;
use App\Models\InterestLog;
use App\Models\InterestRevenue;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * 利息宝
 * @package App\Http\Controllers\Api\v1
 */
class InterestController extends Controller
{


    public function __construct()
    {

    }


    // 转入
    public function recharge(InterestRechargeRequest $request)
    {
        // 验证开关
        $interest_change = Configuration::getItem('interest_change');
        if ($interest_change != 1) {
            $this->errorResponse(Response::HTTP_OK, transtable('60001'), 60001);
        }
        $user = $request->user();
        /*
        $userVip = Vip::getVip($user->vip_id);
        // 验证VIP等级
        $interest_change_vip = Configuration::getItem('interest_change_vip');
        if ($interest_change_vip > $userVip->level) {
            $this->errorResponse(Response::HTTP_OK, sprintf(trans('error.60006'), $interest_change_vip), 60006);
        }
        */

        // 检查支付密码
//        if (!Hash::check($request->password, $user->payment_password)) {
//            $this->errorResponse(Response::HTTP_OK,trans('error.10008'),10008);
//        }

        $re = DB::transaction(function () use ($user, $request) {
            $user = User::sharedLock()->find($user->id);
            if ($request->amount > $user->balance) {
                return 10009;
            }

            $interest = Interest::where('user_id', '=', $user->id)->sharedLock()->first();
            if (!$interest) {
                Interest::create(['user_id' => $user->id]);
                $interest = Interest::where('user_id', '=', $user->id)->sharedLock()->first();
            }
            // 余额-钱
            User::where('id', '=', $user->id)->decrement('balance', $request->amount);
            // 余额-记账
            Account::create([
                'type' => Account::RINTEREST,
                'to' => 0,
                'from' => $user->id,
                'amount' => 0 - $request->amount,
                'level' => 0
            ]);

            // 利息宝+钱
            $interest->balance = $interest->balance + $request->amount;
            $interest->update();
            // 利息宝-记账
            InterestLog::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'balance' => $interest->balance,
                'confirm' => 0
            ]);
            return 0;
        });
        if ($re === 0) {
            return $this->success();
        } else {
            $this->errorResponse(Response::HTTP_OK, transtable($re), $re);
        }
    }

    // 转出
    public function withdrawal(InterestWithdrawalRequest $request)
    {
        // 验证开关
        $interest_withdrawal = Configuration::getItem('interest_withdrawal');
        if ($interest_withdrawal != 1) {
            $this->errorResponse(Response::HTTP_OK, transtable('60002'), 60002);
        }

        $user = $request->user();
        // 检查支付密码
//        if (!Hash::check($request->password, $user->payment_password)) {
//            $this->errorResponse(Response::HTTP_OK,trans('error.10008'),10008);
//        }

        $re = DB::transaction(function () use ($user, $request) {
            $interest = Interest::where('user_id', '=', $user->id)->sharedLock()->first();
            if (!$interest || $request->amount > $interest->balance) {
                return 60003;
            }
            // 利息宝-钱
            $interest->balance = $interest->balance - $request->amount;
            $interest->confirm_balance = $interest->confirm_balance - $request->amount;
            $interest->update();
            // 利息宝-记账
            InterestLog::create([
                'user_id' => $user->id,
                'amount' => 0 - $request->amount,
                'balance' => $interest->balance,
                'confirm' => 1
            ]);

            // 余额+钱
            User::where('id', '=', $user->id)->increment('balance', $request->amount);
            // 余额-记账
            Account::create([
                'type' => Account::WINTEREST,
                'to' => $user->id,
                'from' => 0,
                'amount' => $request->amount,
                'level' => 0
            ]);
            return 0;
        });

        if ($re === 0) {
            return $this->success();
        } else {
            $this->errorResponse(Response::HTTP_OK, transtable($re), $re);
        }
    }

    //个人利息宝统计
    public function statistics(Request $request)
    {
        $user = $request->user();
        $key = sprintf('%s:%d', Interest::CacheKey, $user->id);
        if (!Cache::has($key)) {
            $data['interest_annual_yield'] = Configuration::getItem('interest_annual_yield');  //年化收益
            $data['balance'] = Interest::where('user_id', $user->id)->value('balance');  //利息宝余额
            empty($data['balance']) && $data['balance'] = 0;
            $data['today_interest'] = InterestRevenue::where('user_id', $user->id)->whereDate('created_at', Carbon::today())->sum('amount');  //今日收益
            $data['week_interest'] = InterestRevenue::where('user_id', $user->id)->where('created_at', '>', Carbon::today()->startOfWeek())->sum('amount');  //本周收益
            $data['month_interest'] = InterestRevenue::where('user_id', $user->id)->where('created_at', '>', Carbon::today()->firstOfMonth())->sum('amount');  //本月收益
            $data['total_interest'] = InterestRevenue::where('user_id', $user->id)->sum('amount');  //总收益
            Cache::put($key, $data, 60 * 60);
        }
        return $this->success(Cache::get($key));
    }

    //转入转出记录
    public function logs(Request $request)
    {
        $user = $request->user();
        $page = $request->get('page', 1);
        //$limit = $request->get('limit', 10);
        $limit = 10;
        $page = $this->paging($page, $limit);

        $key = sprintf('%d_interest_logs_%d', $user->id, $page);
        $tag = sprintf('%s_%d', InterestLog::CacheTag, $user->id);
        if (!Cache::tags($tag)->has($key)) {
            $list = InterestLog::select('id', 'amount', 'balance', 'created_at')
                ->where('user_id', $user->id)
                ->offset($page)->limit($limit)->orderBy('created_at', 'desc')
                ->get();
            Cache::tags($tag)->put($key, $list, 60 * 60);
        }
        return $this->success(JsonResource::collection(Cache::tags($tag)->get($key)));
    }

    //收益记录
    public function revenues(Request $request)
    {
        $user = $request->user();
        $page = $request->get('page', 1);
        //$limit = $request->get('limit', 10);
        $limit = 10;
        $page = $this->paging($page, $limit);
        $key = sprintf('%d_interest_revenues_%d', $user->id, $page);
        $tag = sprintf('%s_%d', InterestRevenue::CacheTag, $user->id);
        if (!Cache::tags($tag)->has($key)) {
            $list = InterestRevenue::select('id', 'amount', 'balance', 'created_at')
                ->where('user_id', $user->id)
                ->offset($page)->limit($limit)->orderBy('created_at', 'desc')
                ->get();
            Cache::tags($tag)->put($key, $list, 60 * 60);
        }
        return $this->success(JsonResource::collection(Cache::tags($tag)->get($key)));
    }

}
