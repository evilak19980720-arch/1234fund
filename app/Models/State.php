<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 城邦
 * @package App\Models
 */
class State extends Model
{
    const CacheKey = 'states';

    public $timestamps = false;

    public static function getStates($id=null)
    {
        if (!Cache::has(self::CacheKey)) {
            Cache::put(self::CacheKey,self::all());
        }
        $states = Cache::get(self::CacheKey);
        if ($id) {
            foreach ($states as $state) {
                if ($state->id == $id) {
                    return $state;
                }
            }
        }
        return $states;
    }
}
