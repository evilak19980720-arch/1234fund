<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Statistics;
use App\Models\User;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->title('数据统计')
            ->description('Description...')
            ->row(function (Row $row) {
                $row->column(1, function (Column $column) {
                });
                if (Admin::user()->isRole('administrator')) {

                    $row->column(3, function (Column $column) {
                        $column->append($this->data_statistics('今日充值', 'red'));
                        $column->append($this->data_statistics('今日充值人数', 'yellow'));
                        $column->append($this->data_statistics('昨日充值'));
                        $column->append($this->data_statistics('今日注册', 'green'));
                        //$column->append($this->data_statistics('今日安装', 'purple'));
                    });
                    $row->column(3, function (Column $column) {
                        $column->append($this->data_statistics('今日提现', 'red'));
                        $column->append($this->data_statistics('今日复充人数', 'yellow'));
                        $column->append($this->data_statistics('昨日提现'));
                        $column->append($this->data_statistics('昨日注册', 'green'));
                        //$column->append($this->data_statistics('昨日安装', 'purple'));
                    });
                    $row->column(3, function (Column $column) {
                        $column->append($this->data_statistics('总充值', 'red'));
                        $column->append($this->data_statistics('今日提现人数', 'yellow'));
                        $column->append($this->data_statistics('总提现'));
                        $column->append($this->data_statistics('总注册', 'green'));
                        //$column->append($this->data_statistics('总安装', 'purple'));
                    });

                }

                if (Admin::user()->isRole('agents')) {

                    $row->column(3, function (Column $column) {
                        $column->append($this->data_statistics('今日充值', 'red'));
                        $column->append($this->data_statistics('今日充值人数', 'yellow'));
                        $column->append($this->data_statistics('昨日充值'));
                        $column->append($this->data_statistics('今日注册', 'green'));
                    });
                    $row->column(3, function (Column $column) {
                        $column->append($this->data_statistics('今日提现', 'red'));
                        $column->append($this->data_statistics('今日复充人数', 'yellow'));
                        $column->append($this->data_statistics('昨日提现'));
                        $column->append($this->data_statistics('昨日注册', 'green'));
                    });
                    $row->column(3, function (Column $column) {
                        $column->append($this->data_statistics('总充值', 'red'));
                        $column->append($this->data_statistics('今日提现人数', 'yellow'));
                        $column->append($this->data_statistics('总提现'));
                        $column->append($this->data_statistics('总注册', 'green'));
                    });

                }


            });
    }

    public function data_statistics($field, $color = 'aqua')
    {

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $icon = 'users';
        $admin_user_id = Admin::user()->isRole('agents') ? Admin::user()->id : 0;
        switch ($field) {
            case '今日充值人数':
                $number = Order::query()->whereIn('user_id', function ($query) use ($admin_user_id) {
                        $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                    })
                    ->where('type', '=', Order::RECHARGE)
                    ->where('state', '=', Order::SUCCESS)
                    ->where('iscount', '=', 0)
                    ->whereDate('created_at', '=', $today)
                    ->groupby('user_id')->get()->count();
                break;
            case '今日复充人数':
                $number = DB::table('orders')
                    ->select('user_id')
                    ->where('type', '=', 1)
                    ->where('state', '=', 1)
                    ->where('iscount', '=', 0)
                    ->join('users', 'orders.user_id', 'users.id')
                    ->where('users.admin_user_id', '=', $admin_user_id)
                    ->whereIn('orders.user_id', function ($query) use ($today, $admin_user_id) {
                        $query->select('orders.user_id')->from('orders')
                            ->join('users', 'orders.user_id', 'users.id')
                            ->where('users.admin_user_id', '=', $admin_user_id)
                            ->where('orders.type', '=', 1)
                            ->where('orders.state', '=', 1)
                            ->where('orders.iscount', '=', 0)
                            ->whereDate('orders.created_at', '=', $today);
                    })
                    ->whereDate('orders.created_at', '<', $today)
                    ->groupby('user_id')
                    //->havingRaw('count(*)>1')
                    ->get()
                    ->count();
                break;
            case '今日提现人数':
                $number = Order::query()->whereIn('user_id', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                })
                    ->where('type', '=', Order::WITHDRAWAL)
                    ->where('state', '=', Order::SUCCESS)
                    ->whereDate('created_at', '=', $today)
                    ->groupBy('user_id')->get()->count();
                break;
            case '总注册':
                $number = User::query()->where('admin_user_id', $admin_user_id)->count();
                break;
            case '总安装':
                $number = Statistics::query()->count();
                $icon = 'arrow-circle-o-down';
                break;
            case '今日安装':
                $number = DB::table('statistics')->whereDate('created_at', '=', $today)->count();
                $icon = 'arrow-circle-o-down';
                break;
            case '昨日安装':
                $number = DB::table('statistics')->whereDate('created_at', '=', $yesterday)->count();
                $icon = 'arrow-circle-o-down';
                break;
            case '今日激活':
                /*
                $number = Record::whereBetween('created_at', [
                    $today->startOfDay()->format('Y-m-d H:i:s'),
                    $today->endOfDay()->format('Y-m-d H:i:s')
                ])->sum('number');
                $icon = 'asterisk';
                */
                break;
            case '昨日激活':
                /*
                $number = Record::whereBetween('created_at', [
                    $yesterday->startOfDay()->format('Y-m-d H:i:s'),
                    $yesterday->endOfDay()->format('Y-m-d H:i:s')
                ])->sum('number');
                $icon = 'asterisk';
                */
                break;
            case '今日注册':
                $number = User::query()->where('admin_user_id', '=', $admin_user_id)->whereDate('created_at', '=', $today)->count();
                break;
            case '昨日注册':
                $number = User::query()->where('admin_user_id', '=', $admin_user_id)->whereDate('created_at', '=', $yesterday)->count();
                break;
            case '今日充值':
                $number = Order::query()->whereIn('user_id', function ($query) use ($admin_user_id) {
                        $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                    })
                        ->where('type', '=', Order::RECHARGE)
                        ->where('state', '=', Order::SUCCESS)
                        ->where('iscount', '=', 0)
                        ->whereDate('created_at', '=', $today)
                        ->sum('amount') / 100;
                $icon = 'dollar';
                break;
            case '今日提现':
                $number = Order::query()->whereIn('user_id', function ($query) use ($admin_user_id) {
                        $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                    })
                        ->where('type', '=', Order::WITHDRAWAL)
                        ->where('state', '=', Order::SUCCESS)
                        ->where('payment_id','<>',0)
                        ->whereDate('created_at', '=', $today)
                        ->sum('actual_amount') / 100;
                $icon = 'dollar';
                break;
            case '昨日充值':
                $number = Order::query()->whereIn('user_id', function ($query) use ($admin_user_id) {
                        $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                    })
                        ->where('type', '=', Order::RECHARGE)
                        ->where('state', '=', Order::SUCCESS)
                        ->where('iscount', '=', 0)
                        ->whereDate('created_at', '=', $yesterday)
                        ->sum('amount') / 100;
                $icon = 'dollar';
                break;
            case '昨日提现':
                $number = Order::query()->whereIn('user_id', function ($query) use ($admin_user_id) {
                        $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                    })
                        ->where('type', '=', Order::WITHDRAWAL)
                        ->where('state', '=', Order::SUCCESS)
                        ->where('payment_id','<>',0)
                        ->whereDate('created_at', '=', $yesterday)
                        ->sum('actual_amount') / 100;
                $icon = 'dollar';
                break;
            case '总充值':
                $number = Order::query()->whereIn('user_id', function ($query) use ($admin_user_id) {
                        $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                    })
                        ->where('type', '=', Order::RECHARGE)
                        ->where('state', '=', Order::SUCCESS)
                        ->where('iscount', '=', 0)
                        ->sum('amount') / 100;
                $icon = 'dollar';
                break;
            case '总提现':
                $number = Order::query()->whereIn('user_id', function ($query) use ($admin_user_id) {
                        $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                    })
                        ->where('type', '=', Order::WITHDRAWAL)
                        ->where('state', '=', Order::SUCCESS)
                        ->where('payment_id','<>',0)
                        ->sum('actual_amount') / 100;
                $icon = 'dollar';
                break;
            default:
                $number = 0;
                break;
        }

        $infoBox = new InfoBox($field, $icon, $color, '', $number);
        return $infoBox->render();
    }
}
