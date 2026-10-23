<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PaymentThirdCode extends Model
{
	use DefaultDatetimeFormat;
    use SoftDeletes;

    protected $table = 'payment_third_codes';

    const NO_OPEN = 0;
    const YES_OPEN = 1;

    const OPEN_STATUS = [
        self::NO_OPEN => '关闭',
        self::YES_OPEN => '开启',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class,'payment_third_pay_id','id');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeOpen($query)
    {
        return $query->where('is_open', self::YES_OPEN);
    }

}
