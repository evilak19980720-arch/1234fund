<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{

    use SoftDeletes;
    use DefaultDatetimeFormat;

    const NO_OPEN = 0;
    const YES_OPEN = 1;

    //代收开关
    const PAY_CLOSE = 0;
    const PAY_OPEN = 1;

    //代付开关
    const WITHDRAWAL_CLOSE = 0;
    const WITHDRAWAL_OPEN = 1;



    const OPEN_STATUS = [
        self::NO_OPEN => '关闭',
        self::YES_OPEN => '开启',
    ];


    //允许选择的支付类型
    const PAY_OPTION = [
        'trc20usdt' => 'Trc20usdt',
        'bep20usdt' => 'Bep20usdt',
        'trx' => 'Trx',
        'dz' => 'DZpay',
        //'sun' => 'Sunpay',
        'wkpluss' => 'Wkpluss',
        'one' => 'OnePay',
        'mgm' => 'MgmPay',
        'et' => 'ETpay',
        //'ch' => 'CHPay',
        'vortaq' => 'Vortaq',
        //'liu' => 'Liu6pay',
        //'wow' => 'Wow',
    ];
    protected $fillable = [
        'type',
        'title',
        'merchant',
        'pay_key',
        'withdrawal_key',
        'password',
        'is_open',
        'server_name',
        'remark',
        'upper',
        'lower',
        'sort',
        'pay_url',
        'pay_query_url',
        'withdrawal_url',
        'withdrawal_query_url',
        'pay_code',
        'withdrawal_code',
        'pay',
        'withdrawal',
        'public_key',
        'private_key',
        'currery'
    ];

    /**
     * @param $query
     * @return mixed
     */
    public function scopeOpen($query)
    {
        return $query->where('is_open', self::YES_OPEN);
    }

    /**
     * @param Builder $builder
     * @param $pay_status
     * @return Builder
     */
    public function scopePay(Builder $builder, $pay_status): Builder
    {
        return $builder->where('pay', '=', $pay_status);
    }

    /**
     * @param Builder $builder
     * @param $withdrawal_status
     * @return Builder
     */
    public function scopeWithdrawal(Builder $builder, $withdrawal_status): Builder
    {
        return $builder->where('withdrawal', '=', $withdrawal_status);
    }


    //充值成功
    public function orderRecharge(): HasMany
    {
        return $this->hasMany(Order::class)->where('type', '=', Order::RECHARGE)->where('state','=',Order::SUCCESS)->select('id','payment_id','amount');
    }

    public function admin()
    {
        return $this->belongsTo(AdminUser::class,'admin_user_id','id');
    }


    public function payment_third_code():HasMany
    {
        return $this->hasMany(PaymentThirdCode::class,'payment_third_pay_id');
    }

}
