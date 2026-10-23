<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class Financing extends Model
{
    use DefaultDatetimeFormat;
    protected $table = 'financing';

    protected $fillable = ['user_id','amount','whatsapp','project_name','project_plan'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
