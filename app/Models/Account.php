<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 账目明细
 * @package App\Models
 */
class Account extends Model
{
    use SoftDeletes;
    use DefaultDatetimeFormat;

    const WITHDRAWAL = 0;   // 提现
    const RECHARGE = 1; // 充值
    const COMMISSION = 2;    // 任务佣金
    const RIncome = 3;  // 营销充值收入
    //const TIncome = 4;  // 营销任务收入
    //const VIP = 5;  // 购买vip
    //const RINTEREST = 6;  // 转入利息宝 支出
    //const WINTEREST = 7;  // 利息宝提出 收入
    const REWARD = 8;  // 活动奖励
    const WITHDRAWAL_FAIL = 9;  // 提现失败
    const INVITE_INCOME = 10; //邀请奖励
    const BUY_FISH = 11; //购买鱼
    //const PRE_FISH = 12; //预约鱼
    const SELL_FISH = 13;//卖出鱼
    //const ACTIVATION = 15;  // 激活
    const FRIENDS_REWARD = 16;  // 朋友圈
    const AGENCY_COMMISSION = 17;  // 购买返利
    const SYSTEM_RECHARGE = 18;  // 系统充值
    //const TRANSFER = 19;  // 众筹宠物转让
    //const FUNDING_BUY = 20;  // 众筹购买
    const RECHARGE_REWARD = 21; //充值赠送
    //const CANCELLED_PRE = 22; //取消预约
    const SYSTEM_DEDUCTION = 23;  // 系统扣款
    //const BALANCE_TRANSFER = 24;  // 余额转移
    //const FUNDING_RETURN = 25; //众筹购买后返水
    const SIGN = 26;//签到奖励
    const REGISTER_REWARD = 27;//注册奖励
    const REVENUE_COMMISSION = 28;  //利润返利实际
    const VIP_GIVE = 29;  //VIP奖励
    const AGENCY_CONTEST = 30;  //代理活动

    protected $fillable = [
        'order_id', 'type', 'to', 'from', 'amount', 'level'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to', 'id');
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from', 'id');
    }

    public static function getIncome($uid)
    {
        $key = sprintf('user%d_income', $uid);
        $income = Cache::get($key);
        if (!$income) {
            $income = self::where('to', '=', $uid)->where('amount', '>', 0)->sum('amount');
            Cache::put($key, $income, 60 * 2);
        }
        return $income;
    }

    public static function getExpenditure($uid)
    {
        $key = sprintf('user%d_expenditure', $uid);
        $expenditure = Cache::get($key);
        if (!$expenditure) {
            $expenditure = self::where('from', '=', $uid)->where('amount', '<', 0)->sum('amount');
            Cache::put($key, $expenditure, 60 * 2);
        }
        return $expenditure;
    }

    // 收益
    public static function getProfit($uid, $today = false)
    {
        if ($today) {
            $key = sprintf(KeysHandler::USER_PROFIT, $uid) . '_' . Carbon::today()->format('Y-m-d');
            if (!Cache::has($key)) {
                $profit = self::where('to', '=', $uid)
                    ->where('amount', '>', 0)
                    ->whereBetween('created_at', [
                        Carbon::today()->startOfDay(),
                        Carbon::today()->endOfDay()
                    ])
                    ->whereIn('type', [self::COMMISSION, self::RIncome, self::TIncome])
                    ->sum('amount');
                Cache::put($key, $profit, KeysHandler::USER_PROFIT_EXPIRE);
            }

            return (int)Cache::get($key);
        }

        $key = sprintf(KeysHandler::USER_PROFIT, $uid);
        if (!Cache::has($key)) {
            $profit = self::where('to', '=', $uid)->where('amount', '>', 0)
                ->whereIn('type', [self::COMMISSION, self::RIncome, self::TIncome])
                ->sum('amount');
            Cache::put($key, $profit, KeysHandler::USER_PROFIT_EXPIRE);
        }
        return (int)Cache::get($key);
    }

    /**
     * 团队收益
     * @param $uid
     * @return mixed
     */
    public static function getTeamProfit($uid)
    {

        // * 暂时改成不缓存  等团队接口加上缓存后 再打开,时间要同步
        $key = sprintf(KeysHandler::USER_TEAM_PROFIT, $uid);

        $profit = Cache::get($key);
        if (null === $profit) {
            $profit = self::where('to', '=', $uid)->where('amount', '>', 0)
                ->whereIn('type', [self::RIncome, 17])
                ->sum('amount');
            Cache::put($key, $profit, KeysHandler::USER_PROFIT_EXPIRE);
        }
        /*
        $profit = self::where('to', '=', $uid)->where('amount', '>', 0)
            ->whereIn('type', [self::RIncome,17])
            ->sum('amount');
        */
        return $profit;
    }

    /**
     * 下级收益
     * @param $id   用户id
     * @param $level    等级
     * @param int $page 页码
     * @param int $limit 每页条数
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getSubordinateProfit($id, $level, $page = 1, $limit = 10)
    {
        $key = sprintf(KeysHandler::USER_SUBORDINATE_PROFIT, $id, $level, $page, $limit);
        $rows = Cache::get($key);

        if (!$rows) {
            $fields = ['users.id', 'users.name', 'avatar', 'amount', 'accounts.created_at'];

            $query = $this->newQuery();
            $query->join('users', 'from', '=', 'users.id');
            $query->where('to', '=', $id)
                ->where('amount', '>', 0);

            if ($level) {
                $query->where('level', $level);
            } else {
                $query->whereIn('level', [1, 2, 3]);
            }

            $rows = $query->select($fields)->offset($page)->limit($limit)->orderBy('accounts.created_at', 'desc')->get();
            Cache::put($key, $rows, 60);
        }

        return $rows;
    }

    /**
     *
     * 账目明细
     * @param $user_id
     * @param string $type income收入 expenditure支出 whole全部
     * @param $mold
     * @param int $page
     * @param int $limit
     * @param null $date
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection|mixed
     */
    public function getAccounts($user_id, string $type, $mold, int $page = 1, int $limit = 10, $date = null)
    {
        $key = sprintf(KeysHandler::USER_ACCOUNT, $user_id, $type, $mold, $page, $limit, $date ?: '');
        $rows = Cache::get($key);
        if (!$rows) {
            $query = $this->newQuery();

            if ($date) {
                $query->whereBetween('created_at', [
                    Carbon::parse($date)->firstOfMonth(),
                    Carbon::parse($date)->lastOfMonth()->addDay(),
                ]);
            }
            if ($mold >= 0) {
                $query->where('type', '=', $mold);
            }else{
                $ids = [0,1,10,11,13,16,28,29,8];
                $query->whereIn('type',$ids);
            }
            switch ($type) {
                case 'income':
                    $query->where('to', '=', $user_id)->where('amount', '>', 0);
                    break;
                case 'expenditure':
                    $query->where('from', '=', $user_id)->where('amount', '<', 0);
                    break;
                case 'whole':
                    // 收入+支出
                    $query->where(function ($query) use ($user_id) {
                        $query->where(function ($query) use ($user_id) {
                            $query->where('to', '=', $user_id)->where('amount', '>', 0);
                        })->orWhere(function ($query) use ($user_id) {
                            $query->where('from', '=', $user_id)->where('amount', '<', 0);
                        });
                    });

                    break;
            }

            $rows = $query->select('id', 'type', 'amount', 'created_at')
                ->offset($page)->limit($limit)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            Cache::put($key, $rows, 60);
        }

        return $rows;
    }

    /**
     * 计算某个下级带来的所有盈利
     * @param $to   受益人id
     * @param $from 提供者id
     * @return mixed
     */
    public function calculateRevenueFromUser($to, $from)
    {
        return $this->where('to', '=', $to)
            ->where('from', '=', $from)
            ->whereIn('type', [self::RIncome, self::AGENCY_COMMISSION])
            ->where('amount', '>', 0)
            ->sum('amount');
    }


    /**
     * 计算购买返利
     * @param User $user 购买用户
     * @param int $amount 购买金额
     */
    public function buyCommission(User $user, int $amount)
    {
        if (Configuration::getItem('buy_rebate') && $user->buy_rebate && $user->relation) {
            $user_ids = explode(',', trim($user->relation, ','));
            $rules = Rule::getTaskRule();

            while (count($user_ids)) {
                $uid = array_pop($user_ids);
                $rule = array_shift($rules);
                if ($rule['rebate_percent'] <= 0) {
                    continue;
                }
                $money = $amount * ($rule['rebate_percent'] / 100);
                $this_user = User::query()->find($uid);
                //$this_user->balance = $this_user->balance + $money;
                $this_user->team_commission = $this_user->team_commission + $money;
                $this_user->save();
                Account::query()->create([
                    'type' => self::AGENCY_COMMISSION,
                    'to' => $uid,
                    'from' => $user->id,
                    'amount' => $money,
                    'level' => $rule['level']
                ]);

                //代理争霸活动
                $AgencyContestRewardModel = Configuration::getItem('AgencyContestRewardModel');
                if($AgencyContestRewardModel == 3){
                    AgencyContestUser::userJoinCommission($this_user, $money);
                }
                if($AgencyContestRewardModel == 1 && $user->parent_id == $uid){ //一级上级才执行
                    AgencyContestUser::userJoinCommission($this_user, $money);
                }

                //增加会员统计的返佣信息
                if ($user_statistic = UserStatistic::query()->where('user_id', '=', $uid)->first()) {
                    $user_statistic->increment('commission', $money);
                }
            }
        }
    }


    /**
     * 本周佣金排名前5
     * @return array
     */
    public function thisWeekCommissionTopFive(): array
    {
        return Cache::remember(KeysHandler::THIS_WEEK_COMMISSION_TOP_FIVE, KeysHandler::THIS_WEEK_COMMISSION_TOP_FIVE_EXPIRE, function () {
            $commission = Account::query()
                ->with('toUser')
                ->whereIn('type', [Account::RIncome, Account::AGENCY_COMMISSION])
                ->where('amount', '>', 0)
                ->select('to', DB::raw('SUM(`amount`) as `amount`'))
                ->groupBy('to')
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->orderByDesc('amount')
                ->limit(5)
                ->get();
            $data = [];
            foreach ($commission as $key => $item) {
                $data[$key]['subordinate'] = $item->toUser->countSubordinate();
                $data[$key]['phone'] = $item->toUser->phone;
                $data[$key]['commission'] = (int)$item->amount;
            }
            return $data;
        });
    }

    //假数据
    public function fakeThisWeekCommissionTopFive()
    {
        return Cache::remember(KeysHandler::FAKE_THIS_WEEK_COMMISSION_TOP_FIVE, now()->endOfWeek(), function () {
            $data = [];
            for ($i = 0; $i < 5; $i++) {
                $item['subordinate'] = rand(20, 30);
                $item['phone'] = rand(5, 9) . rand(100, 999) . rand(100, 999) . rand(100, 999);
                $item['commission'] = (int)str_pad(rand(1000, 3000), 6, 0);
                $data[] = $item;
            }
            return collect($data)->sortByDesc('commission');
        });
    }
}
