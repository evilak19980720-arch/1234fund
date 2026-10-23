<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 订单记录
 * @package App\Models
 */
class OrderLog extends Model
{

    protected $fillable = [
        'admin_id', 'order_id', 'no', 'payment_no', 'remark', 'error', 'paid_at'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
