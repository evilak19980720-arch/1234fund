<?php


namespace App\Models;


use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class PaymentDoc extends Model
{
    use DefaultDatetimeFormat;

    protected $fillable = ['user_id', 'order_id', 'image_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
