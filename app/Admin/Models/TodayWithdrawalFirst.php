<?php

namespace App\Admin\Models;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Facades\Admin;

/**
 * 活动
 * @package App\Models
 */
class TodayWithdrawalFirst extends Model
{

    public function paginate()
    {
        $this_date = Request()->get('this_date');
        $parent_phone = Request()->get('parent_phone');
        $admin_user_id = Admin::user()->isRole('agents') ? Admin::user()->id : 0;
        //查会员是否存在
        $parent_userid = 0;
        if(!empty($parent_phone)){
            $parent_user = User::where('phone','=',$parent_phone)->first();
            if($parent_user){
                $parent_userid = $parent_user->id;
            }
        }
        $check_date = Carbon::parse($this_date)->toDateString();  //日期

            //首次提款
            $list = Order::query()
                ->with('user')
                ->whereIn('user_id', function ($query) use ($admin_user_id,$parent_userid) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                    if(!empty($parent_userid)){
                        $query->where('relation', 'regexp', ',' . $parent_userid . ',$');
                    }
                })
                ->where('type', '=', Order::WITHDRAWAL)
                ->whereNotNull('payment_no')
                ->where('state', '=', Order::SUCCESS)
                ->whereNotIn('user_id', function ($query) use ($check_date, $admin_user_id,$parent_userid) {
                    $query->select('user_id')->from('orders')
                        ->when(!empty($parent_userid),function ($query)use($admin_user_id,$parent_userid){
                            $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                            if(!empty($parent_userid)){
                                $query->where('relation', 'regexp', ',' . $parent_userid . ',$');
                            }
                        })
                        ->where('type', '=', Order::WITHDRAWAL)
                        ->where('state', '=', Order::SUCCESS)
                        ->whereDate('created_at', '<', $check_date);
                })
                ->whereDate('created_at', '=', $check_date)
                ->groupBy('user_id')
                ->orderBy('id')
                ->get();

        foreach ($list as &$val){
            $val->user_name = $val->user->phone;
        }

        return $list;
        //return static::hydrate($list);
    }

    public static function with($relations)
    {
        return new static;
    }




}
