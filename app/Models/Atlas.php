<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 图集
 * @package App\Models
 */
class Atlas extends Model
{
    protected $fillable = ['id', 'pet_id', 'img'];

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function getImgAttribute($img)
    {
        return json_decode($img, true);
    }

    public function setImgAttribute($img)
    {
        if (is_array($img)) {
            $this->attributes['img'] = json_encode($img);
        }
    }
}
