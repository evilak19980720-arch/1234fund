<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

/**
 * 投诉回复
 * @package App\Models
 */
class Reply extends Model
{
    use DefaultDatetimeFormat;

    protected $fillable = ['complaint_id','admin_user_id','user_id','content'];

    // 投诉
    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }
}
