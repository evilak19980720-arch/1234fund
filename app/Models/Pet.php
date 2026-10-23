<?php


namespace App\Models;

use App\Handlers\KeysHandler;
use Illuminate\Support\Facades\Cache;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pet extends Model
{
    protected $guarded = [];
    use DefaultDatetimeFormat;
    use SoftDeletes;


    const JOINT_RENTAL = 0;//合租
    const ENTIRE_RENTAL = 1;//整租

    //整租价格
    const ENTIRE_RENTAL_PRICE = 100000;

    // 图集
    public function atlases()
    {
        return $this->hasOne(Atlas::class);
    }

    public function petOrder()
    {
        return $this->hasMany(PetOrder::class);
    }

    public function petOrderBuy()
    {
        return $this->hasMany(PetOrder::class)->where('type',1);
    }

    public function crowds()
    {
        return $this->hasMany(Crowd::class);
    }

    /**
     * 获取宠物详情
     * */
    static public function getPet($id){
        $key = sprintf(KeysHandler::PET, $id);
        if (!Cache::has($key)) {
            $pet = self::withTrashed()->with('atlases')->where('id', '=', $id)->first();
            if (!$pet) {
                return null;
            }
            Cache::put($key, $pet, KeysHandler::PET_EXPIRE);
        }
        $pet = Cache::get($key);
        return $pet;
    }



}
