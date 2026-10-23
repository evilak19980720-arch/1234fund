<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TelegramGroup extends Model
{
    use DefaultDatetimeFormat;
    protected $table = 'telegram_groups';
    protected $fillable = [
        'name', 'user_id', 'agent_id', 'chart_id', 'group_link', 'bot_id', 'template_id', 'status','last_send_time'
    ];


    // 业务员
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // 代理账号
    public function agent_user()
    {
        return $this->belongsTo(User::class, 'agent_id' , 'id');
    }
    // 机器人
    public function bot()
    {
        return $this->belongsTo(TelegramBots::class,'bot_id', 'id');
    }
    // 模版
    public function template()
    {
        return $this->belongsTo(TelegramTemplate::class,'template_id', 'id');
    }



    //所有下级
    public static function getSubusers($agent_ids)
    {
        return User::query()->select('id')->whereIn('parent_id',$agent_ids)->get()->toArray();
    }

    //所有下级充值
    public static function getSubRecharge($agent_ids, $dateType = 'all')
    {
        $subRecharge = Order::query()
            ->where('type','=',1)
            ->where('state','=',1)
            ->whereHas('user',function ($query)use($agent_ids){
                $query->whereIn('parent_id',$agent_ids);
            });
        if($dateType == 'today'){
            $subRecharge->whereDate('created_at','=', Carbon::now());
        }elseif ($dateType == 'yestoday'){
            $subRecharge->whereDate('created_at','=', Carbon::yesterday());
        }
        return [
            'count'  => $subRecharge->count(),
            'amount' => $subRecharge->sum('amount') / 100
        ];
    }
    //所有下级提现
    public static function getSubWithdrawal($agent_ids, $dateType = 'all')
    {
        $subWithdrawal = Order::query()
            ->where('type','=',0)
            ->where('state','=',1)
            ->whereHas('user',function ($query)use($agent_ids){
                $query->whereIn('parent_id',$agent_ids);
            });
        if($dateType == 'today'){
            $subWithdrawal->whereDate('created_at','=', Carbon::now());
        }elseif ($dateType == 'yestoday'){
            $subWithdrawal->whereDate('created_at','=', Carbon::yesterday());
        }
        return [
            'count'  => $subWithdrawal->count(),
            'amount' => $subWithdrawal->sum('actual_amount') / 100
        ];
    }

    //所有下级首存
    public static function getSubFitstRechargeCount($agent_ids, $dateType = 'all')
    {
        $subRecharge = Order::query()
            ->where('type','=',1)
            ->where('state','=',1)
            ->where('isfirstRecharge','=',1)
            ->whereHas('user',function ($query)use($agent_ids){
                $query->whereIn('parent_id',$agent_ids);
            });
        if($dateType == 'today'){
            $subRecharge->whereDate('created_at','=', Carbon::now());
        }elseif ($dateType == 'yestoday'){
            $subRecharge->whereDate('created_at','=', Carbon::yesterday());
        }
        return $subRecharge->count();
    }








}
