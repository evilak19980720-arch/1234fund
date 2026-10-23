<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Red extends Model
{
    use DefaultDatetimeFormat;

    protected $fillable = [
        'client', 'device', 'user_id', 'amount', 'level', 'closed', 'expires_at', 'state', 'red_withdrew_value', 'invite_quantity', 'red_distribution','level'
    ];

    public function red_log()
    {
        return $this->hasMany(RedLog::Class, 'user_id', 'user_id')->with('users');
    }

    public function user()
    {
        return $this->belongsTo(User::Class);
    }

    //邀请注册红包奖励
    public static function inviteRedReward($invite_code, $user)
    {
        DB::transaction(function () use ($invite_code, $user) {
            $red = Red::query()->where(['user_id' => $invite_code, 'state' => 1])->orderBy('id', 'desc')->first();
            if ($red) {
//                if (time() < $red['expires_at']) {
                $red_withdrew_value = $red->red_withdrew_value; //获取此红包提现所需金额
                $red_distribution = explode(',', $red->red_distribution);
                //$amount = $red_distribution[$red->invite_quantity] * 100;
                $amount = $red_distribution[$red->invite_quantity];
                $red->amount += $amount;
                $red->invite_quantity += 1;
                if ($red->amount >= $red_withdrew_value) {
                    $amount = $amount - ($red->amount - $red_withdrew_value);
                    $red->amount = $red_withdrew_value;
                    $red->state = 2;
                }
                $red->save();
                $res = RedLog::create([
                    'user_id' => $invite_code,
                    'red_id'  => $red->id,
                    'amount' => $amount,
                    'from' => $user->id,
                    'level' => $red->level
                ]);
                $data = [
                    'id' => $res->id,
                    'user_id' => $invite_code,
                    'red_id'  => $red->id,
                    'amount' => $amount,
                    'from' => $user->id,
                    'created_at' => $res->created_at,
                    'updated_at' => $res->updated_at,
                    'users' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar' => $user->avatar
                    ]
                ];
                Cache::forget('red_log:red_id:' . $red->id);
            }
        });
    }

    //判断是否弹出红包

    public function red_switch($user_id = 0)
    {
        $data['red_switch'] = Configuration::getItem('red_switch');
        $red_is_received = 0;
        if ($user_id != 0) {
            $red = $this->where(['user_id' => $user_id])->whereIn('state',[1,2])->first();
            if ($red) {
                $red_is_received = 1;
            }
        }
        $data['red_is_received'] = $data['red_switch'] ? $red_is_received : 1;
        return $data;
    }

    /**
     * 根据id查红包
     * @param $id
     * @return mixed
     */
    public function get($id)
    {
        $key = sprintf(KeysHandler::RED, $id);
        $red = Cache::get($key);
        $red || $red = $this->find($id);
        $red && Cache::put($key, $red, 60);
        return $red;
    }

    /**
     * 判断未登录设备是否领过红包
     * @param $client
     * @param $device
     */
    public function receiveWithoutLogin($client, $device)
    {
        $red = Cache::get(KeysHandler::RED_CLIENT_DEVICE);
        $red || $red = $this->where('client', '=', $client)->where('device', '=', $device)->first();
        // 如果没领并且开启
        if (!$red && Configuration::getItem('red_switch')) {
            $this->client = $client;
            $this->device = $device;
            $this->amount = rand(100, 150) * 100;
            $this->user_id = 0;
            $this->closed = 0;
            $this->save();
            Cache::put(KeysHandler::RED_CLIENT_DEVICE, $red, 60 * 5);
            return $this;
        }

        return $red;
    }

    /**
     * 打开红包
     * @return bool
     */
    public function open()
    {
        $this->closed = 1;
        return $this->save();
    }
}
