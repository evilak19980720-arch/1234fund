<?php

namespace App\Http\Controllers\Api\v1;

use App\Jobs\CsAiReply;
use App\Models\CsMessage;
use App\Models\CsSession;
use App\Models\User;
use App\Services\CustomerService\CsSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * 站内在线客服
 * @package App\Http\Controllers\Api\v1
 */
class CsController extends Controller
{
    /**
     * 客服配置：前端据此决定打开站内客服还是第三方链接
     */
    public function config()
    {
        $internal = CsSettings::mode() === CsSettings::MODE_INTERNAL;

        return $this->success([
            'mode' => CsSettings::mode(),
            'external_url' => $internal ? '' : (string) CsSettings::get('customer_service_url'),
            'welcome' => $internal ? (string) CsSettings::get('cs_welcome') : '',
        ]);
    }

    /**
     * 拉取消息（after_id 之后的新消息；不传则返回最近 50 条）
     */
    public function messages(Request $request)
    {
        $session = $this->session($request);
        $afterId = (int) $request->get('after_id', 0);

        $query = CsMessage::query()->where('session_id', $session->id);
        if ($afterId > 0) {
            $messages = $query->where('id', '>', $afterId)->orderBy('id')->limit(100)->get();
        } else {
            $messages = $query->orderBy('id', 'desc')->limit(50)->get()->reverse()->values();
        }

        return $this->success([
            'status' => $session->status,
            'messages' => $messages->map->toClient()->values(),
        ]);
    }

    /**
     * 发送消息
     */
    public function send(Request $request)
    {
        if (CsSettings::mode() !== CsSettings::MODE_INTERNAL) {
            $this->errorResponse(Response::HTTP_OK, 'Online customer service is not available', 90001);
        }

        $content = trim((string) $request->get('content', ''));
        if ($content === '' || mb_strlen($content) > config('customer_service.max_message_length', 1000)) {
            $this->errorResponse(Response::HTTP_OK, 'Invalid message', 90002);
        }

        $session = $this->session($request);

        $rateKey = 'cs_send:' . $session->id;
        if (RateLimiter::tooManyAttempts($rateKey, config('customer_service.messages_per_minute', 10))) {
            $this->errorResponse(Response::HTTP_OK, 'Too many messages, please try again later', 90003);
        }
        RateLimiter::hit($rateKey, 60);

        // 不再转人工：之前停在“等待人工”的会话交回 AI（后台客服主动接管的 human 会话不受影响）
        if ($session->status === CsSession::STATUS_PENDING) {
            $session->status = CsSession::STATUS_AI;
            $session->admin_unread = 0;
            $session->save();
        }

        $message = $session->addMessage(CsMessage::ROLE_USER, $content);

        if ($session->status === CsSession::STATUS_AI) {
            if (!CsSettings::aiEnabled()) {
                $this->whenAiUnavailable($session);
            } elseif ($this->overDailyLimit($session)) {
                $session->addMessage(CsMessage::ROLE_SYSTEM, 'You have reached today\'s limit for the AI assistant. Please come back tomorrow.');
            } else {
                // 响应发出后在当前进程里生成回复，不依赖队列进程
                CsAiReply::dispatchAfterResponse($session->id);
            }
        }

        return $this->success([
            'status' => $session->status,
            'message' => $message->toClient(),
        ]);
    }

    protected function session(Request $request): CsSession
    {
        $user = $this->user();
        $guestToken = (string) $request->get('guest_token', '');
        if (!$user && !preg_match('/^[A-Za-z0-9\-]{16,64}$/', $guestToken)) {
            $this->errorResponse(Response::HTTP_OK, 'Invalid guest token', 90004);
        }

        return CsSession::current($user, $user ? '' : $guestToken);
    }

    protected function user(): ?User
    {
        try {
            return auth('api')->user();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * AI 关闭时：引导到第三方客服链接
     */
    protected function whenAiUnavailable(CsSession $session): void
    {
        $url = trim((string) CsSettings::get('customer_service_url'));
        $session->addMessage(CsMessage::ROLE_SYSTEM, $url !== ''
            ? 'Online customer service is busy. Please contact us here: ' . $url
            : 'Online customer service is busy. Please try again later.');
    }

    protected function overDailyLimit(CsSession $session): bool
    {
        $limit = (int) CsSettings::get('cs_ai_daily_limit');
        if ($limit <= 0) {
            return false;
        }
        $key = 'cs_ai_daily:' . $session->id . ':' . date('Ymd');
        Cache::add($key, 0, 86400);
        return Cache::increment($key) > $limit;
    }
}
