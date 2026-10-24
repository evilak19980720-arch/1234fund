<?php

namespace App\Services\CustomerService\Clients;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OpenAI 兼容的 /chat/completions 接口（DeepSeek、OpenAI、Gemini、通义千问、智谱、Kimi、豆包等）
 */
class OpenAiCompatibleClient implements AiClient
{
    const MAX_TOOL_ROUNDS = 4;

    protected $apiKey;
    protected $model;
    protected $baseUrl;
    protected $maxTokensParam;

    public function __construct(string $apiKey, string $model, string $baseUrl, string $maxTokensParam = 'max_tokens')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->maxTokensParam = $maxTokensParam;
    }

    public function run(string $system, array $history, array $tools, callable $runTool): array
    {
        $messages = array_merge([['role' => 'system', 'content' => $system]], $history);
        $toolDefs = array_map(function ($tool) {
            return ['type' => 'function', 'function' => $tool];
        }, $tools);
        $result = ['content' => '', 'tools' => [], 'usage' => ['input_tokens' => 0, 'output_tokens' => 0]];

        for ($round = 0; $round <= self::MAX_TOOL_ROUNDS; $round++) {
            // 最后一轮不再提供工具，让模型给出文字回复
            $response = $this->request($messages, $round < self::MAX_TOOL_ROUNDS ? $toolDefs : []);
            $message = $response['choices'][0]['message'] ?? null;
            if (!is_array($message)) {
                throw new RuntimeException('接口返回格式异常');
            }
            $result['usage']['input_tokens'] += (int) ($response['usage']['prompt_tokens'] ?? 0);
            $result['usage']['output_tokens'] += (int) ($response['usage']['completion_tokens'] ?? 0);

            if (empty($message['tool_calls'])) {
                $result['content'] = trim((string) ($message['content'] ?? ''));
                return $result;
            }

            $messages[] = [
                'role' => 'assistant',
                'content' => $message['content'] ?? '',
                'tool_calls' => $message['tool_calls'],
            ];
            foreach ($message['tool_calls'] as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
                $result['tools'][] = $name;
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => json_encode($runTool($name, $args), JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        return $result;
    }

    protected function request(array $messages, array $tools): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            $this->maxTokensParam => 1000,
        ];
        if ($tools) {
            $payload['tools'] = $tools;
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->acceptJson()
            ->post($this->baseUrl . '/chat/completions', $payload);

        if (!$response->successful()) {
            $error = $response->json('error.message') ?: $response->json('message') ?: mb_substr($response->body(), 0, 300);
            throw new RuntimeException('HTTP ' . $response->status() . '：' . (is_string($error) ? $error : json_encode($error, JSON_UNESCAPED_UNICODE)));
        }

        return $response->json() ?: [];
    }
}
