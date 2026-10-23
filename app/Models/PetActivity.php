<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class PetActivity extends Model
{
    use DefaultDatetimeFormat;
    protected $fillable = ['id','act_buying_price'];

    public function getStartAtAttribute($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
    public function setStartAtAttribute($value)
    {
        $this->attributes['start_at'] = strtotime($value);
    }

    public function getEndAtAttribute($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
    public function setEndAtAttribute($value)
    {
        $this->attributes['end_at'] = strtotime($value);
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class,'pet_id','id');
    }
}
