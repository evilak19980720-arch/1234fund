<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FriendImage extends Model
{
    public $timestamps = false;

    protected $fillable = ['friend_id','img'];

    public function friend()
    {
        return $this->belongsTo(Friend::class);
    }
}
