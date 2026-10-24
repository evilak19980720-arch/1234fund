<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 客服 AI 服务商配置（每个服务商一条，Key 加密存储）
 * @package App\Models
 */
class CsAiProvider extends Model
{
    protected $fillable = ['provider', 'api_key', 'model', 'base_url'];

    protected $casts = [
        'api_key' => 'encrypted',
    ];

    protected $hidden = ['api_key'];
}
