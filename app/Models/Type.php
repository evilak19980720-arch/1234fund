<?php

namespace App\Models;

use App\Handlers\KeysHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 任务类型
 * @package App\Models
 */
class Type extends Model
{
    public $timestamps = false;

    // 任务
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function vip()
    {
        return $this->belongsTo(Vip::class);
    }

    /**
     * 获取任务类型
     * @param null $id
     * @return Type|Type[]|\Illuminate\Database\Eloquent\Collection|mixed
     */
    public static function getType($id=null)
    {
        $types = Cache::get(KeysHandler::TYPE);
        if (!$types) {
            $types = self::with(['vip' => function($query) {
                $query->select('id', 'name', 'level');
            }])->where('state','=',1)->orderBY('sort')->get();
            Cache::put(KeysHandler::TYPE,$types,KeysHandler::TYPE_EXPIRE);
        }

        if ($id) {
            foreach ($types as $type) {
                if ($type['id'] == $id) {
                    return $type;
                }
            }
            return null;
        }

        return $types;
    }
}
