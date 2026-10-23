<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use App\Models\Agent;

class Customer extends Model
{
    use SoftDeletes;
    use DefaultDatetimeFormat;


    //是否禁用
    const NORMAL = 0;
    const FORBID = 1;


    const TYPE_MAP = [
        'Telegram' => 'Telegram',
        'WhatsApp' => 'WhatsApp'
    ];

    const ICON_MAP = [
        'Telegram' => '/img/telegram.jpg',
        'WhatsApp' => '/img/whatsApp.jpg'
    ];

    const FORBID_MAP = [
        self::NORMAL => '启用',
        self::FORBID => '禁用'
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function getCustomer(int $id = 0, $uid = 0)
    {
        $customers = Cache::get(KeysHandler::CUSTOMER);
        if (!$customers) {
            $customers = self::all();
            Cache::put(KeysHandler::CUSTOMER, $customers, 60 * 60);
        }
        $agent = Agent::getAgent($uid);
        if ($customers) {
            foreach ($customers as $customer) {
                if ($id && $id == $customer->id) {
                    return $customer;
                }
                if ($agent == $customer->admin_user_id) {
                    $data[$customer->platform][] = $customer;
                }
            }

            return array_values(array_map(function ($var) use ($uid) {
                return $uid && $var ? $var[$uid % count($var)] : $var[array_rand($var)];
            }, $data));
        }

        return $customers;
    }


    /**
     * 获取用户分配的客服，如果未分配则随机分配一个
     * @param User $user
     * @return array|mixed
     */
    public function userCustomer($user)
    {
        if (!empty($user)) {
            $customer = $user->customer;
            if ($customer && $customer->forbid == self::NORMAL) {
                if ($customer->type == 'WhatsApp') {
                    $customer->url = 'https://api.whatsapp.com/send?phone=' . $customer->contact;
                    $customer->contact = '+' . $customer->contact;
                } else {
                    $customer->url = 'https://t.me/' . $customer->contact;
                    $customer->contact = '@' . $customer->contact;
                }
                return $customer;
            }
            //没有客服或者客服未查找到就随机分配一个客服
            $customer = $this->distributionCustomer($user->admin_user_id);

        }else{
            //没有客服或者客服未查找到就随机分配一个客服
            $customer = $this->distributionCustomer(0);
        }

        if (!$customer) {
            return [];
        }
        if (!empty($user)) {
            $user->customer_id = $customer->id;
            $user->save();
        }
        return $customer;
    }

    public function admin()
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id', 'id');
    }


    public function DataProcess($param)
    {
        if (!$param->isEmpty()) {
            foreach ($param as $value) {
                if ($value->type == 'WhatsApp') {
                    $value->url = 'https://api.whatsapp.com/send?phone=+' . $value->contact;
                    $value->contact = '+' . $value->contact;
                } else {
                    $value->url = 'https://t.me/' . $value->contact;
                    $value->contact = '@' . $value->contact;
                }
            }
        }
        return $param;
    }

    //获取分配业务员信息
    public function distributionCustomer(int $admin_user_id)
    {
        $full_customer = self::query()->withCount('users')
            ->where('admin_user_id', '=', $admin_user_id)
            ->where('forbid', '=', self::NORMAL)
            ->get();
        $full_customer = self::DataProcess($full_customer);
        if (!count($full_customer)) {
            return [];
        }
        //已客户数量升序分配
        return $full_customer->sortBy('users_count')->first();
    }

}
