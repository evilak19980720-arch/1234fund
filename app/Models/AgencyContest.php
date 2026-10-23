<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * 活动
 * @package App\Models
 */
class AgencyContest extends Model
{
    use SoftDeletes;
    use DefaultDatetimeFormat;
    public $timestamps = false;

    protected $table='agency_contests';
    protected $fillable = [
        'issue', 'start_time', 'end_time', 'status', 'reward'
    ];

    const STATE_MAP = [
        1 => '进行中',
        2 => '已结束'
    ];


    public function agency_contest_users()
    {
        return $this->hasMany(AgencyContestUser::class)->orderByDesc('isfirst')->orderByDesc('active_member_count')->orderByDesc('accumulated_commission')->orderBy('created_at');
    }


    public function agency_contest_first_user()
    {
        return $this->hasOne(AgencyContestUser::class)->orderByDesc('isfirst')->orderByDesc('active_member_count')->orderByDesc('accumulated_commission')->orderBy('created_at');
    }





}
