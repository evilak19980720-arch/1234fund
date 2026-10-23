<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminUser extends Model
{
    public function getAvatarAttribute($value)
    {
        return $value ?: '';
    }

    public function agentconf()
    {
        return $this->hasOne(AgentConf::class);
    }

}
