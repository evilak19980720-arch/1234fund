<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

/**
 * 客服会话
 * @package App\Models
 */
class CsSession extends Model
{
    use DefaultDatetimeFormat;

    const STATUS_AI = 'ai';           // AI接待
    const STATUS_PENDING = 'pending'; // 等待人工
    const STATUS_HUMAN = 'human';     // 人工接待
    const STATUS_CLOSED = 'closed';   // 已结束

    const STATUS_MAP = [
        self::STATUS_AI => 'AI接待',
        self::STATUS_PENDING => '等待人工',
        self::STATUS_HUMAN => '人工接待',
        self::STATUS_CLOSED => '已结束',
    ];

    protected $fillable = ['user_id', 'guest_token', 'status', 'transfer_reason', 'admin_user_id', 'admin_unread', 'lang', 'ip', 'last_message_at'];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(CsMessage::class, 'session_id');
    }

    public function isGuest(): bool
    {
        return $this->user_id == 0;
    }

    /**
     * 是否由人工处理中（AI 不再回复）
     */
    public function isHandledByHuman(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_HUMAN]);
    }

    /**
     * 获取或创建当前用户/游客的会话（已结束的会话会重新打开为 AI 接待）
     */
    public static function current(?User $user, string $guestToken): ?self
    {
        if ($user) {
            $session = self::query()->where('user_id', $user->id)->latest('id')->first();
        } elseif ($guestToken !== '') {
            $session = self::query()->where('user_id', 0)->where('guest_token', $guestToken)->latest('id')->first();
        } else {
            return null;
        }

        if (!$session) {
            $session = self::query()->create([
                'user_id' => $user ? $user->id : 0,
                'guest_token' => $user ? '' : $guestToken,
                'status' => self::STATUS_AI,
                'lang' => (string) request()->header('accept-language', ''),
                'ip' => (string) getIp2(),
            ]);
        } elseif ($session->status === self::STATUS_CLOSED) {
            $session->status = self::STATUS_AI;
            $session->admin_user_id = 0;
            $session->transfer_reason = '';
            $session->save();
        }

        return $session;
    }

    /**
     * 写入一条消息并更新会话
     */
    public function addMessage(string $role, string $content, int $adminUserId = 0, ?array $meta = null): CsMessage
    {
        $message = $this->messages()->create([
            'role' => $role,
            'content' => $content,
            'admin_user_id' => $adminUserId,
            'meta' => $meta,
        ]);

        $this->last_message_at = $message->created_at;
        if ($role === CsMessage::ROLE_USER && $this->isHandledByHuman()) {
            $this->admin_unread += 1;
        }
        $this->save();

        return $message;
    }

    /**
     * 转人工
     */
    public function transferToHuman(string $reason): void
    {
        if ($this->isHandledByHuman()) {
            return;
        }
        $this->status = self::STATUS_PENDING;
        $this->transfer_reason = mb_substr($reason, 0, 255);
        $this->admin_unread = max(1, $this->admin_unread);
        $this->save();
    }
}
