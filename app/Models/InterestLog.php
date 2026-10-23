<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

/**
 * 利息宝账户
 * @package App\Models
 */
class InterestLog extends Model
{
    use DefaultDatetimeFormat;

    protected $fillable = [
        'user_id', 'amount', 'balance', 'confirm'
    ];
    const CacheTag = "user_interest_logs";
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
