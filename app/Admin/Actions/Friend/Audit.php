<?php

namespace App\Admin\Actions\Friend;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\Friend;
use App\Models\User;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class Audit extends BatchAction
{
    public $name = '批量审核';
    private $expire = 10;
    private $ok;
    private $amount;

    public function handle(Collection $collection)
    {
        $this->ok = request()->get('ok');
        $this->amount = Configuration::getItem('friends_reward');

        foreach ($collection as $model) {
            Cache::lock(sprintf('friendAudit:%d', $model->id), $this->expire)->get(function () use ($model) {
                DB::transaction(function () use ($model) {
                    $friend = Friend::where('id', '=', $model->id)->lockForUpdate()->first();

                    if ($friend->ok == 0) {
                        $friend->ok = $this->ok;

                        if ($this->ok == 1 && $this->amount > 0) {
                            if(!Friend::where('user_id','=',$model->user_id)->whereDate('created_at','=',$friend->created_at)->where('reward','=',1)->first()){
                                $friend->reward = 1;  //已奖励
                                User::where('id', '=', $model->user_id)->increment('balance', $this->amount);
                                Account::create([
                                    'order_id' => 0,
                                    'type' => Account::FRIENDS_REWARD,
                                    'to' => $model->user_id,
                                    'form' => 0,
                                    'amount' => $this->amount,
                                    'level' => 0
                                ]);
                            }
                        }

                        $friend->save();
                    }
                });
            });
        }

        return $this->response()->success('Success message...')->refresh();
    }

    public function form()
    {
        $this->radio('ok', '结果')->options([
            1 => '通过',
            2 => '拒绝',
        ])->required();
    }

}
