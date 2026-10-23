<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class TelegramTemplate extends Model
{
	use DefaultDatetimeFormat;
    use SoftDeletes;

    protected $table = 'telegram_templates';
    protected $fillable = [
        'user_id', 'name', 'content', 'photo'
    ];

    // 业务员
    public function user()
    {
        return $this->belongsTo(User::class);
    }



}
