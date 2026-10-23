<?php

namespace App\Admin\Controllers;

use App\Models\Account;
use App\Models\Agent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Statistics;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Show;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SumController extends AdminController
{
    public function index(Content $content)
    {
        return $content->title('数据总览')->row(function (Row $row) {
            $row->column(3, function (Column $column) {
                $column->append($this->data_statistics('今日未提现资金','teal'));
                $column->append($this->data_statistics('当前余款','teal'));
            });
            $row->column(3, function (Column $column) {
                $column->append($this->data_statistics('昨日未提现资金','teal'));
                $column->append($this->data_statistics('TRX总充值','teal'));
            });
            $row->column(3, function (Column $column) {
                $column->append($this->data_statistics('总未提现资金','teal'));
                $column->append($this->data_statistics('INR总充值','teal'));
            });
            $row->column(3, function (Column $column) {
                $column->append($this->data_statistics('TRC20总充值','teal'));
                $column->append($this->data_statistics('EGP总充值','teal'));
            });
        });
    }

    public function data_statistics($field, $color = 'aqua')
    {

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $icon = 'users';
        $admin_user_id = Admin::user()->isRole('agents') ? Admin::user()->id : 0;
        switch ($field) {
            case '今日未提现资金':
                $number = DB::table('orders')
                        ->join('users','orders.user_id','users.id')
                        ->where('users.admin_user_id','=',$admin_user_id)
                        ->where('orders.type','=',0)
                        ->where('orders.state','=',-1)
                        ->whereDate('orders.created_at', '=' , $today)
                        ->sum('actual_amount') / 100;
                $icon = 'dollar';
                break;
            case '昨日未提现资金':
                $number = DB::table('orders')
                        ->join('users','orders.user_id','users.id')
                        ->where('users.admin_user_id','=',$admin_user_id)
                        ->where('orders.type','=',0)
                        ->where('orders.state','=',-1)
                        ->whereDate('orders.created_at', '=' , $yesterday)
                        ->sum('orders.actual_amount') / 100;
                $icon = 'dollar';
                break;
            case '总未提现资金':
                $number = DB::table('orders')
                        ->join('users','orders.user_id','users.id')
                        ->where('users.admin_user_id','=',$admin_user_id)
                        ->where('orders.type','=',0)
                        ->where('orders.state','=',-1)
                        ->sum('orders.actual_amount') / 100;
                $icon = 'dollar';
                break;
            case 'TRC20总充值':
                $number = DB::table('orders')
                        ->where(function ($query){
                            $query->where('platform','=','trc20')->orWhere('platform','=','USDT-TRC');
                        })
                        ->where('type','=',Order::RECHARGE)
                        ->where('state','=',1)
                        ->sum('amount') / 100;
                $icon = 'dollar';
                break;
            case 'TRX总充值':
                $number = DB::table('orders')
                        ->where('platform','=','TRX')
                        ->where('type','=',Order::RECHARGE)
                        ->where('state','=',1)
                        ->sum('amount') / 100;
                $icon = 'dollar';
                break;
            case 'INR总充值':
                $number = DB::table('orders')
                        ->whereIn('payment_id',function ($query){
                            $query->select('id')->from('payments')->where('currery','=','INR');
                        })
                        ->where('type','=',Order::RECHARGE)
                        ->where('state','=',1)
                        ->sum('amount') / 100;
                $icon = 'dollar';
                break;
            case 'EGP总充值':
                $number = DB::table('orders')
                        ->whereIn('payment_id',function ($query){
                            $query->select('id')->from('payments')->where('currery','=','EGP');
                        })
                        ->where('type','=',Order::RECHARGE)
                        ->where('state','=',1)
                        ->sum('amount') / 100;
                $icon = 'dollar';
                break;
            case '当前余款':
                $number = User::query()->where('hierarchy_id','=',1)
                    ->whereIn('id',function ($query){
                        $query->select('user_id')->from('orders')->where('type','=',1)->where('state',1)->groupby('user_id');
                    })->sum('balance') / 100;
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
