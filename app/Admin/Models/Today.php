<?php

namespace App\Admin\Models;

use App\Models\FundOrder;
use App\Models\Order;
use App\Models\PetOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Cache;

/**
 * 活动
 * @package App\Models
 */
class Today extends Model
{

    /**
     * $grid->column('thisdate', '日期');
     * $grid->column('reg', '今日注册');
     * $grid->column('recharge', '今日充值');
     * $grid->column('recharge_user', '今日充值人数');
     * $grid->column('again', '今日复充');
     * $grid->column('withdrawal', '今日提现');
     * $grid->column('withdrawal_user', '今日提现人数');
     * $grid->column('install', '今日安装');
     */
    public function paginate()
    {
        $this_date = Request()->get('this_date');
        $parent_phone = Request()->get('parent_phone');
        if (empty($this_date['start']) || empty($this_date['end']) ) {
            $this_date['end'] = Carbon::today();
            $this_date['start'] = Carbon::today()->sub('7 days');
        } else {
            $this_date['end'] = Carbon::parse($this_date['end']);
            $this_date['start'] = Carbon::parse($this_date['start']);
        }
        $result = $this_row = [];
        $admin_user_id = Admin::user()->isRole('agents') ? Admin::user()->id : 0;
        //$end_date = $this_date['end']->diffInDays($this_date['start']) > 7 ? $this_date['start']->copy()->addDays(7) : $this_date['end'];
        $end_date  = $this_date['end'];


        //查会员是否存在
        $parent_userid = 0;
        if(!empty($parent_phone)){
            $parent_user = User::where('phone','=',$parent_phone)->first();
            if($parent_user){
                $parent_userid = $parent_user->id;
            }
        }
        //info($parent_userid);
        for ($check_date = $end_date; $check_date->gte($this_date['start']); $check_date->sub('1 days')) {
            $check_date_copy = $check_date->copy();
            $this_row['this_date'] = $check_date->toDateString();  //日期
            $cachekey = 'today:'.$this_row['this_date'];
            if($check_date_copy->lt(Carbon::today())){
                if(Cache::get($cachekey)){
                    $result[] = Cache::get($cachekey);
                    continue;
                }
            }

            //今日充值
            $this_row['recharge'] = Order::query()->whereIn('user_id', function ($query) use ($admin_user_id,$parent_userid) {
                $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                if(!empty($parent_userid)){
                    $query->where('relation', 'regexp', ',' . $parent_userid . ',$');
                }
            })
                ->where('type', '=', Order::RECHARGE)
                ->where('state', '=', Order::SUCCESS)
                ->where('iscount', '=', 0)
                ->whereDate('created_at', '=', $check_date)
                ->sum('amount');
            //今日充值人数
            $this_row['recharge_user'] = Order::query()->whereIn('user_id', function ($query) use ($admin_user_id,$parent_userid) {
                $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);

                if(!empty($parent_userid)){
                    $query->where('relation', 'regexp', ',' . $parent_userid . ',$');
                }

            })
                ->where('type', '=', Order::RECHARGE)
                ->where('state', '=', Order::SUCCESS)
                ->where('iscount', '=', 0)
                ->whereDate('created_at', '=', $check_date)
                ->groupby('user_id')->get()->count();
            //今日复充人数
            $this_row['again'] = DB::table('orders')
                ->join('users', 'orders.user_id', 'users.id')
                ->where('users.admin_user_id', '=', $admin_user_id)
                ->when(!empty($parent_userid),function ($q)use($parent_userid){
                    $q->where('users.relation', 'regexp', ',' . $parent_userid . ',$');
                })
                ->select('orders.user_id')
                ->where('orders.type', '=', 1)
                ->where('orders.state', '=', 1)
                ->where('orders.iscount', '=', 0)
                ->whereIn('orders.user_id', function ($query) use ($check_date, $admin_user_id,$parent_userid) {
                    $query->select('orders.user_id')->from('orders')
                        ->join('users', 'orders.user_id', 'users.id')
                        ->where('users.admin_user_id', '=', $admin_user_id)
                        ->when(!empty($parent_userid),function ($q)use($parent_userid){
                            $q->where('users.relation', 'regexp', ',' . $parent_userid . ',$');
                        })
                        //->where('users.relation', 'regexp', ',' . $parent_userid . ',$')
                        ->where('orders.type', '=', 1)
                        ->where('orders.state', '=', 1)
                        ->where('orders.iscount', '=', 0)
                        ->whereDate('orders.created_at', '=', $check_date);
                })
                ->whereDate('orders.created_at', '<', $check_date)
                ->groupBy('orders.user_id')
                //->havingRaw('count(*)>1')
                ->get()
                ->count();

            //今日提现
            $this_row['withdrawal'] = Order::query()->whereIn('user_id', function ($query) use ($admin_user_id,$parent_userid) {
                $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                if(!empty($parent_userid)){
                    $query->where('relation', 'regexp', ',' . $parent_userid . ',$');
                }
            })
                ->where('type', '=', Order::WITHDRAWAL)
                ->whereNotNull('payment_no')
                ->where('state', '=', Order::SUCCESS)
                ->whereDate('created_at', '=', $check_date)
                ->sum('actual_amount');
            //未提现资金
            $this_row['withdrawal_pending'] = Order::query()->whereIn('user_id', function ($query) use ($admin_user_id,$parent_userid) {
                $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                if(!empty($parent_userid)){
                    $query->where('relation', 'regexp', ',' . $parent_userid . ',$');
                }
            })
                ->where('type', '=', Order::WITHDRAWAL)
                ->where('state', '=', Order::REVIEW)
                ->whereDate('created_at', '=', $check_date)
                ->sum('actual_amount');
            //今日提现人数
            $this_row['withdrawal_user'] = Order::query()->whereIn('user_id', function ($query) use ($admin_user_id, $parent_userid) {
                $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                if(!empty($parent_userid)){
                    $query->where('relation', 'regexp', ',' . $parent_userid . ',$');
                }
            })
                ->where('type', '=', Order::WITHDRAWAL)
                ->where('state', '=', Order::SUCCESS)
                ->whereDate('created_at', '=', $check_date)
                ->groupBy('user_id')->get()->count();
            //今日注册++
            $this_row_reg_query = User::query()->where('admin_user_id', '=', $admin_user_id)->whereDate('created_at', '=', $check_date);
            if(!empty($parent_userid)){
                $this_row_reg_query->where('relation', 'regexp', ',' . $parent_userid . ',$');
            }
            $this_row['reg'] = $this_row_reg_query->count();

            //预计到账
            $wait_petorder = PetOrder::query()
                ->where('type', '=', 1)
                //->where('state', '=', 0)
                //->whereRaw("TIMESTAMPDIFF(HOUR,`start_at`,'{$this_row['this_date']}') >= (`day` * 24) + 24")
                ->whereRaw("TIMESTAMPDIFF(DAY,`start_at`,'{$this_row['this_date']}') = `day`")
                ->sum('selling_amount');
            $this_row['wait_petorder'] = $wait_petorder;


            $withdrawal_first_list = Order::query()
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
            //首次提款
            $withdrawal_first_amount = $withdrawal_first_list->sum('actual_amount');

            $withdrawal_first_count = $withdrawal_first_list->count();
            $this_row['withdrawal_first_amount'] = $withdrawal_first_amount;
            $this_row['withdrawal_first_count'] = $withdrawal_first_count;

            /*
            //今日投注
            $this_row['fund'] = FundOrder::query()
                ->whereIn('user_id', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                })
                ->whereDate('created_at', '=', $check_date)
                ->sum('amount');
            //今日投注中奖
            $win_fund_order = FundOrder::query()->with(['fundPeriod' => function ($builder) {
                $builder->select('id', 'total_price');
            }])
                ->whereIn('user_id', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                })
                ->whereDate('created_at', '=', $check_date)
                ->where('win', '=', FundOrder::WIN_YES)
                ->select('id', 'fund_period_id', 'win', 'quantity')
                ->get();
            $this_row['win_fund'] = $win_fund_order->sum(function ($item) {
                return $item->quantity * $item->fundPeriod->total_price;
            });
            //今日投注人数
            $this_row['fund_user'] = FundOrder::query()
                ->whereIn('user_id', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                })
                ->whereDate('created_at', '=', $check_date)
                ->groupBy('user_id')
                ->get()->count();
            //今日中奖人数
            $this_row['win_fund_user'] = FundOrder::query()
                ->whereIn('user_id', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                })
                ->whereDate('created_at', '=', $check_date)
                ->where('win', '=', FundOrder::WIN_YES)
                ->groupBy('user_id')
                ->get()->count();
            //今日投注次数
            $this_row['fund_count'] = FundOrder::query()
                ->whereIn('user_id', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                })
                ->whereDate('created_at', '=', $check_date)
                ->count();;
            //今日中奖次数
            $this_row['win_fund_count'] = FundOrder::query()
                ->whereIn('user_id', function ($query) use ($admin_user_id) {
                    $query->select('id')->from('users')->where('admin_user_id', '=', $admin_user_id);
                })
                ->whereDate('created_at', '=', $check_date)
                ->where('win', '=', FundOrder::WIN_YES)
                ->count();
            if (Admin::user()->isRole('administrator')) {
                //今日安装
                $this_row['install'] = DB::table('statistics')->whereDate('created_at', '=', $check_date)->count();
            }
            */

            if($check_date_copy->lt(Carbon::today())) {
                Cache::put($cachekey, $this_row);
            }
            $result[] = $this_row;
        }
        return static::hydrate($result);
    }

    public static function with($relations)
    {
        return new static;
    }


}
