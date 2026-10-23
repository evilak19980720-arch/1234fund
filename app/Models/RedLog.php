<?php


namespace App\Models;


use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class RedLog extends Model
{
    use DefaultDatetimeFormat;

    protected $fillable = ['user_id', 'amount', 'from','red_id'];

    public function getCreatedAtAttribute($val)
    {
        //return date('d/m/Y', strtotime($val));
        return strtotime($val) * 1000;
    }

    public function users()
    {
        return $this->belongsTo(User::Class, 'from', 'id');
    }

    public function orders()
    {
        return $this->hasMany(PetOrder::Class,'user_id','from')->where('type','=',1);
    }

}
