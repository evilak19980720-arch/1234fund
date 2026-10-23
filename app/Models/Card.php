<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * 银行卡
 * @package App\Models
 */
class Card extends Model
{
    protected $fillable = [
        'user_id','number', 'username','bank_id','phone','email','province','city','ifsc','address','currery','default_card'
    ];

    const DEFAULT_CURRERY = 'TRX';

    use DefaultDatetimeFormat;
    use SoftDeletes;
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function provinces()
    {
        return $this->belongsTo(State::class,'province','id');
    }
    public function citys()
    {
        return $this->belongsTo(State::class,'city','id');
    }


    /**
     * 是否绑定银行卡
     * @param $uid
     * @return bool
     */
    public static function haveBindCard($uid)
    {
        $key = sprintf(KeysHandler::HAVE_CARD,$uid);

        $result = Cache::get($key);
        if (null === $result) {
            $result = Card::where('user_id','=',$uid)->value('id');
            Cache::put($key, $result, 60);
        }

        return $result ? true : false;
    }
}
