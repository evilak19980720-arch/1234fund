<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

/**
 * 利息宝账户
 * @package App\Models
 */
class InterestRevenue extends Model
{
    use DefaultDatetimeFormat;

    protected $fillable = [
        'user_id', 'amount', 'balance'
    ];
    const CacheTag = "user_interest_revenues";
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
