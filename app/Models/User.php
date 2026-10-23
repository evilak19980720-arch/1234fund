<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use DefaultDatetimeFormat;

    const WHATAPP_URL = 'https://api.whatsapp.com/send/?phone';
    const TELEGRAM_URL = 'https://t.me/';

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'admin_user_id', 'avatar', 'name', 'phone', 'password', 'payment_password', 'real_name', 'vip', 'currery','balance', 'frozen', 'inviter', 'relation', 'rebate', 'ip_register', 'activation', 'hidephone','google_id','country','device_number','email','whatsapp','telegram','whatsapp_hide','telegram_hide','discount_switch','p','sub_first_bindcard','sub_first_petorder','parent_id','method'
    ];

    public function hierarchy()
    {
        return $this->belongsTo(Hierarchy::class);
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getRealNameAttribute($value)
    {
        return $value ?: '';
    }

    // 银行卡
    public function cards()
    {
        return $this->hasMany(Card::class);
    }
    public function card()
    {
        return $this->hasOne(Card::class)->where('default_card','=',1);
    }

    // 投诉举报
    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function vip()
    {
        return $this->belongsTo(Vip::class);
    }

    public function countries()
    {
        return $this->belongsTo(Country::class,'country','country_i');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function orderRecharge()
    {
        return $this->hasMany(Order::class)->where('type','=',1)->where('state','=',1);
    }

    public function orderWithdrawal()
    {
        return $this->hasMany(Order::class)->where('type','=',0)->where('state','=',1);
    }

    public function friends()
    {
        return $this->hasMany(Friend::class);
    }

    public function financing()
    {
        return $this->hasMany(Financing::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }


    protected function getLevel($level)
    {
        return sprintf(',%d,%s$', $this->id, str_repeat('[0-9]+,', $level - 1));
    }

    public function pet_orders()
    {
        return $this->hasMany(PetOrder::Class)->where('type','=',1);
    }


    /**
     * 查询下级
     * @param int $level 等级，0表示所有
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function subordinate(int $level = 0, int $page = 1, int $limit = 10, $date = '')
    {
        $query = $this->select('id', 'avatar', 'name', 'created_at')->offset($page)->limit($limit);

        if ($date) {
            $query->whereBetween('created_at', [
                Carbon::parse($date)->firstOfMonth(),
                Carbon::parse($date)->lastOfMonth()->addDay(),
            ]);
        }

        return $level ? $query->where('relation', 'regexp', $this->getLevel($level))->get()
//                      : $query->where('relation', 'like', "%,$this->id,%")->get();
            : $query->whereRaw("find_in_set(?,relation)", $this->id)->get();
//            : $query->whereRaw("MATCH(relation) against(?)",$this->id)->get();

    }


    /**
     * 查询下级 以及下级的收益
     * @param int $level 等级，0表示所有
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function subordinate_amount(int $level = 0, int $page = 1, int $limit = 10, $date = '', $showzero = 0, $phone = '', $column = '', $sort = '')
    {
        $query = $this->select('users.id', 'users.avatar', 'users.name', 'users.created_at', 'users.hidephone')
            ->selectRaw('sum(accounts.amount) as amount')->offset($page)->limit($limit);
        if ($date) {
            $query->whereBetween('users.created_at', [
                Carbon::parse($date)->firstOfMonth(),
                Carbon::parse($date)->lastOfMonth()->addDay(),
            ]);
        }
        $level ? $query->where('relation', 'regexp', $this->getLevel($level))
//            : $query->where('relation', 'like', "%,$this->id,%");
            : $query->whereRaw("find_in_set(?,relation)", $this->id);
//            : $query->whereRaw("MATCH(relation) against(?)",$this->id);

        if (!empty($phone)) {
            $query->where('users.phone', 'like', $phone . '%');
        }
        $showzero ? $join = 'join' : $join = 'leftJoin';
        $query->$join('accounts', function ($query_join) {
            $query_join->on('users.id', '=', 'accounts.from')
                ->where('accounts.to', '=', $this->id)
                ->whereIn('accounts.type', [Account::RIncome, Account::AGENCY_COMMISSION])
                ->where('accounts.amount', '>', 0);
        })->groupBy('users.id');

        //排序
        if (!empty($column) && !empty($sort)) {
            switch ($column) {
                case 'contribution':
                    $query->orderBy('amount', $sort);
                    break;
                case 'activation':
                    $query->orderBy('users.activation', $sort);
                    break;
                case 'time':
                    $query->orderBy('users.created_at', $sort);
                    break;
                default:
                    $query->orderBy('amount', 'desc');
                    break;
            }
        }
        return $query->get();
    }




    //获取下级
    public function child(int $level = 0)
    {
        $key = sprintf(KeysHandler::USER_CHILD, $this->id, $level);
        $users = Cache::get($key);
        if (null === $users) {
            $users = $level ? self::query()->where('relation', 'regexp', $this->getLevel($level))->get()
                : self::query()->whereRaw("find_in_set(?,relation)", $this->id)->get();
            Cache::put($key, $users, 60 * 2);
        }
        return $users;
    }

    static public function hideName($user, $arrt_user)
    {
        $user->link = "";
        if ($user->hidephone == 1 || $user->hierarchy_id == 4) {
            $user->name = sprintf('%s****%s', substr($user->name, 0, 3), substr($user->name, -3));
        } else {
            $user->link = sprintf('%s=+%d', self::WHATAPP_URL, $user->name);
        }

        if ($arrt_user && $user->id == $arrt_user->id) {
            $user->link = "";
        }
        unset($user->hierarchy_id);
        unset($user->hidephone);
        return $user;
    }


    //获取用户有多少下级购买了订单
    public function subordinateBuy($user): int
    {
        $child = self::query()->where('relation', 'regexp', sprintf(',%d,%s$', $user->id, str_repeat('[0-9]+,', 0)))->get();
        return PetOrder::query()->whereIn('user_id', $child->pluck('id'))
            ->where('pet_id','<>',59)
            ->whereIn('type', [1, 3])
            ->groupBy('user_id')
            ->get()
            ->count();
    }


    public function getSubordinateLevelCount(User $me, ?Carbon $date, ?bool $showzero, ?int $level, string $phone): int
    {
        $query = self::query()->select([
            'users.id',
            'users.avatar',
            'users.created_at',
            'users.phone',
        ]);
        $level ? $query->where('relation', 'regexp', $this->getLevel($level)) : $query->whereRaw("find_in_set(?,relation)", $me->id);

        if (!empty($phone)) {
            $query->where('users.phone', 'like', $phone . '%');
        }

        $join = $showzero ? 'join' : 'leftJoin';
        $query->$join('accounts', function ($query_join) use ($me) {
            $query_join->on('users.id', '=', 'accounts.from')
                ->where('accounts.to', '=', $me->id)
                ->whereIn('accounts.type', [Account::RIncome, Account::AGENCY_COMMISSION])
                ->where('accounts.amount', '>', 0);
        })->groupBy('users.id');

        if ($date) {
            $query->where('users.created_at', '>=', $date->firstOfMonth()->startOfDay()->toDateTimeString());
            $query->where('users.created_at', '<=', $date->lastOfMonth()->endOfDay()->toDateTimeString());
        }

        $collection = $query->get();
        return count($collection);
    }


    public function getSubordinateLevelRechargeCount(User $me, ?Carbon $date, ?bool $showzero, ?int $level, string $phone): int
    {
        $query = self::query()->select([
            'users.id',
            'users.avatar',
            'users.created_at',
            'users.phone',
        ]);
        $level ? $query->where('relation', 'regexp', $this->getLevel($level)) : $query->whereRaw("find_in_set(?,relation)", $me->id);

        if (!empty($phone)) {
            $query->where('users.phone', 'like', $phone . '%');
        }

        $join = $showzero ? 'join' : 'leftJoin';
        $query->$join('accounts', function ($query_join) use ($me) {
            $query_join->on('users.id', '=', 'accounts.from')
                ->where('accounts.to', '=', $me->id)
                ->whereIn('accounts.type', [Account::RIncome, Account::AGENCY_COMMISSION])
                ->where('accounts.amount', '>', 0);
        })->groupBy('users.id');

        if ($date) {
            $query->where('users.created_at', '>=', $date->firstOfMonth()->startOfDay()->toDateTimeString());
            $query->where('users.created_at', '<=', $date->lastOfMonth()->endOfDay()->toDateTimeString());
        }

        $collection = $query->get();
        return count($collection);
    }


    /**
     * 统计下级人数--注册时间
     * @param int $level 等级，0全部
     * @return int
     */
    public function countSubordinate(int $level = 0, $date_type = 'all', $date_start = null, $date_end = null)
    {
        if(!empty($date_start) || !empty($date_end) ){
            return $level ? self::query()
                ->where('relation', 'regexp', $this->getLevel($level))
                ->when(!empty($date_start) , function ($q) use($date_start){
                    $q->where('created_at', '>',$date_start);
                })
                ->when(!empty($date_start) , function ($q) use($date_end){
                    $q->where('created_at', '<',$date_end);
                })->count()
                : self::query()
                    ->whereRaw("find_in_set(?,relation)", $this->id)
                    ->when(!empty($date_start) , function ($q) use($date_start){
                        $q->where('created_at', '>',$date_start);
                    })
                    ->when(!empty($date_end) , function ($q) use($date_end){
                        $q->where('created_at', '<',$date_end);
                    })->count();
        }

        $key = sprintf(KeysHandler::USER_SUBORDINATE, $this->id, $level);
        $users = Cache::get($key);
        if (null === $users) {
            $users = $level ? self::query()
                ->where('relation', 'regexp', $this->getLevel($level))
                ->when(!empty($date_start) , function ($q) use($date_start){
                    $q->where('created_at', '>',$date_start);
                })
                ->when(!empty($date_end) , function ($q) use($date_end){
                    $q->where('created_at', '<',$date_end);
                })->count()
                : self::query()
                    ->whereRaw("find_in_set(?,relation)", $this->id)
                ->when(!empty($date_start) , function ($q) use($date_start){
                    $q->where('created_at', '>',$date_start);
                })
                ->when(!empty($date_end) , function ($q) use($date_end){
                    $q->where('created_at', '<',$date_end);
                })->count();
            Cache::put($key, $users, 60 * 2);
        }

        return (int)$users;
    }


    /**
     * 统计下级投资人数--按照订单时间
     * @param int $level 等级，0全部
     * @return int
     */
    public function countSubordinatePetOrder(int $level = 0, $date_type = 'all', $date_start = null, $date_end = null)
    {

        if(!empty($date_start) || !empty($date_end) ){

            return  $level ?
                PetOrder::query()
                    ->whereIn('user_id' , function ($query) use ($date_start,$date_end, $level) {
                        $query->select('id')->from('users')->where('relation', 'regexp', $this->getLevel($level));
                    })
                    ->when(!empty($date_start) , function ($q) use($date_start){
                        $q->where('created_at', '>',$date_start);
                    })
                    ->when(!empty($date_end) , function ($q) use($date_end){
                        $q->where('created_at', '<',$date_end);
                    })
                    ->groupBy('user_id')
                    ->get()
                    ->count()
                :
                PetOrder::query()
                    ->whereIn('user_id' , function ($query) use ($date_start,$date_end, $level) {
                        $query->select('id')->from('users')->whereRaw("find_in_set(?,relation)", $this->id);
                    })
                    ->when(!empty($date_start) , function ($q) use($date_start){
                        $q->where('created_at', '>',$date_start);
                    })
                    ->when(!empty($date_end) , function ($q) use($date_end){
                        $q->where('created_at', '<',$date_end);
                    })
                    ->groupBy('user_id')
                    ->get()
                    ->count();

        }


        $key = sprintf(KeysHandler::USER_SUBORDINATE_PETORDER, $this->id, $level);

        $users = Cache::get($key);
        if (null === $users) {
            $users = $level ?
                PetOrder::query()
                    ->whereIn('user_id' , function ($query) use ($date_start,$date_end, $level) {
                        $query->select('id')->from('users')->where('relation', 'regexp', $this->getLevel($level));
                    })
                    ->when(!empty($date_start) , function ($q) use($date_start){
                        $q->where('created_at', '>',$date_start);
                    })
                    ->when(!empty($date_end) , function ($q) use($date_end){
                        $q->where('created_at', '<',$date_end);
                    })
                    ->groupBy('user_id')
                    ->get()
                    ->count()
                :
                PetOrder::query()
                    ->whereIn('user_id' , function ($query) use ($date_start,$date_end, $level) {
                        $query->select('id')->from('users')->whereRaw("find_in_set(?,relation)", $this->id);
                    })
                    ->when(!empty($date_start) , function ($q) use($date_start){
                        $q->where('created_at', '>',$date_start);
                    })
                    ->when(!empty($date_end) , function ($q) use($date_end){
                        $q->where('created_at', '<',$date_end);
                    })
                    ->groupBy('user_id')
                    ->get()
                    ->count();
            Cache::put($key, $users, 60 * 2);
        }
        return (int)$users;
    }

    /**
     * 统计下级充值人数--按照订单时间
     * @param int $level 等级，0全部
     * @return int
     */
    public function countSubordinateOrder(int $level = 0, $date_type = 'all', $date_start = null, $date_end = null)
    {

        if(!empty($date_start) || !empty($date_end) ){

            return  $level ?
                Order::query()
                    ->whereIn('user_id' , function ($query) use ($date_start,$date_end, $level) {
                        $query->select('id')->from('users')->where('relation', 'regexp', $this->getLevel($level));
                    })
                    ->when(!empty($date_start) , function ($q) use($date_start){
                        $q->where('created_at', '>',$date_start);
                    })
                    ->when(!empty($date_end) , function ($q) use($date_end){
                        $q->where('created_at', '<',$date_end);
                    })
                    ->where('type','=',Order::RECHARGE)
                    ->where('state','=',Order::SUCCESS)
                    ->groupBy('user_id')
                    ->get()
                    ->count()
                :
                Order::query()
                    ->whereIn('user_id' , function ($query) use ($date_start,$date_end, $level) {
                        $query->select('id')->from('users')->whereRaw("find_in_set(?,relation)", $this->id);
                    })
                    ->when(!empty($date_start) , function ($q) use($date_start){
                        $q->where('created_at', '>',$date_start);
                    })
                    ->when(!empty($date_end) , function ($q) use($date_end){
                        $q->where('created_at', '<',$date_end);
                    })
                    ->where('type','=',Order::RECHARGE)
                    ->where('state','=',Order::SUCCESS)
                    ->groupBy('user_id')
                    ->get()
                    ->count();

        }

        $key = sprintf(KeysHandler::USER_SUBORDINATE_ORDER, $this->id, $level);

        $users = Cache::get($key);
        if (null === $users) {
            $users = $level ?
                Order::query()
                    ->whereIn('user_id' , function ($query) use ($date_start,$date_end, $level) {
                        $query->select('id')->from('users')->where('relation', 'regexp', $this->getLevel($level));
                    })
                    ->when(!empty($date_start) , function ($q) use($date_start){
                        $q->where('created_at', '>',$date_start);
                    })
                    ->when(!empty($date_end) , function ($q) use($date_end){
                        $q->where('created_at', '<',$date_end);
                    })
                    ->where('type','=',Order::RECHARGE)
                    ->where('state','=',Order::SUCCESS)
                    ->groupBy('user_id')
                    ->get()
                    ->count()
                :
                Order::query()
                    ->whereIn('user_id' , function ($query) use ($date_start,$date_end, $level) {
                        $query->select('id')->from('users')->whereRaw("find_in_set(?,relation)", $this->id);
                    })
                    ->when(!empty($date_start) , function ($q) use($date_start){
                        $q->where('created_at', '>',$date_start);
                    })
                    ->when(!empty($date_end) , function ($q) use($date_end){
                        $q->where('created_at', '<',$date_end);
                    })
                    ->where('type','=',Order::RECHARGE)
                    ->where('state','=',Order::SUCCESS)
                    ->groupBy('user_id')
                    ->get()
                    ->count();
            Cache::put($key, $users, 60 * 2);
        }
        return (int)$users;
    }


    /**
     * 统计下级首冲人数--按照订单时间
     * @param int $level 等级，0全部
     * @return int
     */
    public function countSubordinateOrderFirst(int $level = 0, $date_type = 'all', $date_start = null, $date_end = null)
    {

        if(!empty($date_start) || !empty($date_end) ){
            return $level ?
                Order::query()
                    ->whereIn('user_id' , function ($query) use ($date_start,$date_end, $level) {
                        $query->select('id')->from('users')->where('relation', 'regexp', $this->getLevel($level));
                    })
                    ->when(!empty($date_start) , function ($q) use($date_start){
                        $q->where('created_at', '>',$date_start);
                    })
                    ->when(!empty($date_end) , function ($q) use($date_end){
                        $q->where('created_at', '<',$date_end);
                    })
                    ->where('type','=',Order::RECHARGE)
                    ->where('state','=',Order::SUCCESS)
                    ->where('isfirstRecharge','=',1)
                    ->groupBy('user_id')
                    ->get()
                    ->count()
                :
                Order::query()
                    ->whereIn('user_id' , function ($query) use ($date_start,$date_end, $level) {
                        $query->select('id')->from('users')->whereRaw("find_in_set(?,relation)", $this->id);
                    })
                    ->when(!empty($date_start) , function ($q) use($date_start){
                        $q->where('created_at', '>',$date_start);
                    })
                    ->when(!empty($date_end) , function ($q) use($date_end){
                        $q->where('created_at', '<',$date_end);
                    })
                    ->where('type','=',Order::RECHARGE)
                    ->where('state','=',Order::SUCCESS)
                    ->where('isfirstRecharge','=',1)
                    ->groupBy('user_id')
                    ->get()
                    ->count();
        }


        $key = sprintf(KeysHandler::USER_SUBORDINATE_ORDERFIRST, $this->id, $level);

        $users = Cache::get($key);
        if (null === $users) {
            $users = $level ?
                Order::query()
                    ->whereIn('user_id' , function ($query) use ($date_start,$date_end, $level) {
                        $query->select('id')->from('users')->where('relation', 'regexp', $this->getLevel($level));
                    })
                    ->when(!empty($date_start) , function ($q) use($date_start){
                        $q->where('created_at', '>',$date_start);
                    })
                    ->when(!empty($date_end) , function ($q) use($date_end){
                        $q->where('created_at', '<',$date_end);
                    })
                    ->where('type','=',Order::RECHARGE)
                    ->where('state','=',Order::SUCCESS)
                    ->where('isfirstRecharge','=',1)
                    ->groupBy('user_id')
                    ->get()
                    ->count()
                :
                Order::query()
                    ->whereIn('user_id' , function ($query) use ($date_start,$date_end, $level) {
                        $query->select('id')->from('users')->whereRaw("find_in_set(?,relation)", $this->id);
                    })
                    ->when(!empty($date_start) , function ($q) use($date_start){
                        $q->where('created_at', '>',$date_start);
                    })
                    ->when(!empty($date_end) , function ($q) use($date_end){
                        $q->where('created_at', '<',$date_end);
                    })
                    ->where('type','=',Order::RECHARGE)
                    ->where('state','=',Order::SUCCESS)
                    ->where('isfirstRecharge','=',1)
                    ->groupBy('user_id')
                    ->get()
                    ->count();
            Cache::put($key, $users, 60 * 2);
        }

        return (int)$users;
    }


}
