<?php


namespace App\Models;


use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class Hierarchy extends Model
{
    use DefaultDatetimeFormat;
    protected $table = 'hierarchy';
    protected $fillable = ['title'];

}
