<?php

namespace App\Services\CustomerService;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * DeepSeek 对话接口（OpenAI 兼容格式）
 */
class DeepSeekClient
{
    /**
     * @param array $messages OpenAI 格式的消息
     * @param array $tools    OpenAI 格式的工具定义
     * @return array choices[0].message 和 usage
     */
    public function chat(array $messages, array $tools = []): array
    {
        $config = config('customer_service.deepseek');
        if (empty($config['api_key'])) {
            throw new RuntimeException('DEEPSEEK_API_KEY 未配置');
        }

        $payload = [
            'model' => $config['model'],
            'messages' => $messages,
            'temperature' => 0.3,
            'max_tokens' => 800,
        ];
        if ($tools) {
            $payload['tools'] = $tools;
        }

        $response = Http::withToken($config['api_key'])
            ->timeout($config['timeout'])
            ->acceptJson()
            ->post(rtrim($config['base_url'], '/') . '/chat/completions', $payload);

        if (!$response->successful()) {
            throw new RuntimeException('DeepSeek 请求失败：HTTP ' . $response->status() . ' ' . mb_substr($response->body(), 0, 300));
        }

        $message = $response->json('choices.0.message');
        if (!is_array($message)) {
            throw new RuntimeException('DeepSeek 返回格式异常');
        }

        return [
            'message' => $message,
            'usage' => $response->json('usage') ?: [],
        ];
    }
}
