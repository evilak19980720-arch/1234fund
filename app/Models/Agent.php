<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'admin_user_id', 'user_id'
    ];

    static public function getAgent($uid)
    {
        return User::query()->where('id','=',$uid)->value('admin_user_id');
    }

}
