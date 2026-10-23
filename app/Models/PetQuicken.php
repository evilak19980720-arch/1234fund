<?php


namespace App\Models;


use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class PetQuicken extends Model
{
    protected $fillable = ['user_id', 'order_id', 'hours',];
    use DefaultDatetimeFormat;


}
