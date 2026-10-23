<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Friend extends Model
{
    use DefaultDatetimeFormat;
    use SoftDeletes;

    protected $fillable = ['user_id','content','up','times','reward'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function imgs()
    {
        return $this->hasMany(FriendImage::class);
    }
}
