<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Crowd extends Model
{
    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function issues()
    {
        return $this->hasMany(Issue::class);
    }
}
