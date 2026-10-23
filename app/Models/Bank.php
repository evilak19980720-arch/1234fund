<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * 银行
 * @package App\Models
 */
class Bank extends Model
{
    const BankCache = 'banks';
    const Expire = 3600 * 24 * 7;

    public $timestamps = false;

    /**
     * 从缓存获取银行
     * @return mixed
     */
    public static function getBank($id = null)
    {
        if (!Cache::has(self::BankCache)) {
            $banks = Bank::all()->toArray();
            array_multisort(array_column($banks, 'name'), SORT_ASC, SORT_STRING, $banks);
            Cache::put(self::BankCache, $banks, self::Expire);
        }

        $banks = Cache::get(self::BankCache);
        if ($id) {
            foreach ($banks as $bank) {
                if ($bank['id'] == $id) {
                    return $bank;
                }
            }
        } else {
            return $banks;
        }
    }
}
