<?php

namespace App\Jobs;

use App\Models\CsMessage;
use App\Models\CsSession;
use App\Services\CustomerService\AiAgent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI 客服回复
 */
class CsAiReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 180;

    protected $sessionId;

    public function __construct(int $sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function handle(AiAgent $agent)
    {
        // 在 PHP-FPM 请求结束后执行，放宽执行时间限制
        @set_time_limit(180);

        // 同一会话串行处理，用户连发多条时只回复一次
        Cache::lock('cs_ai_reply:' . $this->sessionId, 170)->block(60, function () use ($agent) {
            $session = CsSession::query()->find($this->sessionId);
            if (!$session || $session->status !== CsSession::STATUS_AI) {
                return;
            }
            $last = CsMessage::query()->where('session_id', $session->id)
                ->whereIn('role', [CsMessage::ROLE_USER, CsMessage::ROLE_AI, CsMessage::ROLE_AGENT])
                ->orderBy('id', 'desc')->first();
            if (!$last || $last->role !== CsMessage::ROLE_USER) {
                return;
            }

            try {
                $reply = $agent->reply($session);
            } catch (\Throwable $e) {
                Log::error('AI客服回复失败', ['session_id' => $session->id, 'error' => $e->getMessage()]);
                $session->addMessage(CsMessage::ROLE_SYSTEM, 'The assistant is temporarily unavailable. Please try again later.');
                return;
            }

            $session->refresh();
            $content = $reply['content'] !== '' ? $reply['content'] : 'Sorry, I could not answer that. Please describe your question in another way.';
            $session->addMessage(CsMessage::ROLE_AI, $content, 0, $reply['meta']);
        });
    }
}
