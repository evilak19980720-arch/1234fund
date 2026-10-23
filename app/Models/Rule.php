<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 营销规则
 * @package App\Models
 */
class Rule extends Model
{
    const CacheKey = 'rules';
    const Expire = 3600*24*7;
    const RECHARGE = 0; // 充值
    const TASK = 1; // 任务

    public $timestamps = false;

    public static function getRule($id=null)
    {
        $rules = Cache::get(self::CacheKey);
        if (!$rules) {
            $rules = self::all();
            $rules &&  Cache::put(self::CacheKey,$rules,self::Expire);
        }

        if (!$id) {
            return $rules;
        }

        foreach ($rules as $rule) {
            if ($id == $rule['id']) {
                return $rule;
            }
        }
        return null;
    }

    // 任务规则
    public static function getTaskRule()
    {
        return array_filter(self::getRule()->toArray(),function ($var) {
            return $var['type'] == self::TASK;
        });
    }

    // 充值规则
    public static function getRechargeRule()
    {
        return array_filter(self::getRule()->toArray(),function ($var) {
            return $var['type'] == self::RECHARGE;
        });
    }
}
