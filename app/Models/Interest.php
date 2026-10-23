<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 利息宝账户
 * @package App\Models
 */
class Interest extends Model
{

    public $timestamps = false;

    const CacheKey = "interest_statistics";

    protected $fillable = [
        'user_id', 'balance', 'confirm_balance', 'revenue_switch'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
