<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TelegramBots extends Model
{
    use DefaultDatetimeFormat;
    protected $table = 'telegram_bots';
    protected $fillable = [
        'bot_name','bot_token', 'status'
    ];

    public function group()
    {
        return $this->hasMany(TelegramGroup::class,'bot_id');
    }





}
