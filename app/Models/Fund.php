<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fund extends Model
{
    //宠物
    /**
     * @var mixed
     */
    private $id;


    //开关
    const OFF = 0;
    const ON = 1;

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    //彩种排期
    public function fundPeriod()
    {
        return $this->hasMany(FundPeriod::class);
    }


}
