<?php


namespace App\Models;


use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class RedLadder extends Model
{
    use DefaultDatetimeFormat;

    protected $fillable = ['level', 'red_withdrew_value', 'red_value', 'red_invite_num', 'pet_id', 'subordinate_pet_id', 'state','subordinate_num'];

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function subordinate_pet()
    {
        return $this->belongsTo(Pet::class, 'subordinate_pet_id', 'id');
    }
}
