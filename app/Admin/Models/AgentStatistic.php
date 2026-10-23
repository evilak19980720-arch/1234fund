<?php

namespace App\Admin\Models;

use App\Models\Order;
use App\Models\User;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class  AgentStatistic extends Model
{
    //今日充值，今日提现，总充值，总提现，总注册，昨日充值，昨日提现，昨天注册，
    public function paginate()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $agents = Administrator::query()->where('id', '!=', 6)->get();
        $result = [];
        foreach ($agents as $agent) {
            if (!$agent->isRole('agents')) {
                continue;
            }
            $data['admin_name'] = $agent->username;
            $admin_user_id = $agent->id;
            //今日充值
            $data['today_recharge'] = Order::query()
                ->whereIn('user_id', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                })
                ->where('type', '=', Order::RECHARGE)
                ->where('state', '=', Order::SUCCESS)
                ->where('iscount', '=', 0)
                ->whereDate('created_at', '=', $today)
                ->sum('amount');

            //今日提现
            $data['today_withdrawal'] = Order::query()
                ->whereIn('user_id', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                })
                ->where('type', '=', Order::WITHDRAWAL)
                ->where('state', '=', Order::SUCCESS)
                ->whereDate('created_at', '=', $today)
                ->sum('amount');

            //昨日充值
            $data['yesterday_recharge'] = Order::query()
                ->whereIn('user_id', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                })
                ->where('type', '=', Order::RECHARGE)
                ->where('state', '=', Order::SUCCESS)
                ->where('iscount', '=', 0)
                ->whereDate('created_at', '=', $yesterday)
                ->sum('amount');

            //昨日提现
            $data['yesterday_withdrawal'] = Order::query()
                ->whereIn('user_id', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                })
                ->where('type', '=', Order::WITHDRAWAL)
                ->where('state', '=', Order::SUCCESS)
                ->whereDate('created_at', '=', $yesterday)
                ->sum('amount');
            //总充值
            $data['recharge'] = Order::query()
                ->whereIn('user_id', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                })
                ->where('type', '=', Order::RECHARGE)
                ->where('state', '=', Order::SUCCESS)
                ->where('iscount', '=', 0)
                ->sum('amount');

            //总提现
            $data['withdrawal'] = Order::query()
                ->whereIn('user_id', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                })
                ->where('type', '=', Order::WITHDRAWAL)
                ->where('state', '=', Order::SUCCESS)
                ->sum('amount');

            $data['today_register'] = User::query()->where('admin_user_id', '=', $admin_user_id)->whereDate('created_at', '=', $today)->count();
            //昨日注册
            $data['yesterday_register'] = User::query()->where('admin_user_id', '=', $admin_user_id)->whereDate('created_at', '=', $yesterday)->count();
            //总注册
            $data['register'] = User::query()->where('admin_user_id', '=', $admin_user_id)->count();
            $result[] = $data;
        }
        return static::hydrate($result);
    }

    public static function with($relations)
    {
        return new static;
    }
}
