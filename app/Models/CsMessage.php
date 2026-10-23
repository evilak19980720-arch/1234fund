<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

/**
 * 客服消息
 * @package App\Models
 */
class CsMessage extends Model
{
    use DefaultDatetimeFormat;

    const ROLE_USER = 'user';     // 用户
    const ROLE_AI = 'ai';         // AI客服
    const ROLE_AGENT = 'agent';   // 人工客服
    const ROLE_SYSTEM = 'system'; // 系统提示

    const ROLE_MAP = [
        self::ROLE_USER => '用户',
        self::ROLE_AI => 'AI',
        self::ROLE_AGENT => '人工',
        self::ROLE_SYSTEM => '系统',
    ];

    protected $fillable = ['session_id', 'role', 'content', 'admin_user_id', 'meta'];

    protected $casts = [
        'meta' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(CsSession::class, 'session_id');
    }

    /**
     * 返回给前端的字段（不含 meta）
     */
    public function toClient(): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'created_at' => $this->created_at ? $this->created_at->timestamp : 0,
        ];
    }
}
