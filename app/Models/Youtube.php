<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

/**
 * youtube
 * @package App\Models
 */
class Youtube extends Model
{
    use DefaultDatetimeFormat;

    protected $table = 'youtube';

    protected $fillable = [
        'title', 'link', 'people', 'author', 'imported'
    ];
}
