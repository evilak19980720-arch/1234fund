<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class PetOrderTransfer extends Model
{
    use DefaultDatetimeFormat;
    public $timestamps = false;
    protected $fillable = [
        'pet_order_id','user_id','to_user_id','transfer_at'
    ];



}
