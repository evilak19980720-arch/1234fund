<?php

namespace App\Services\CustomerService\Clients;

use Anthropic\Client;
use RuntimeException;

/**
 * Anthropic Claude（官方 PHP SDK：composer require anthropic-ai/sdk）
 */
class AnthropicClient implements AiClient
{
    const MAX_TOOL_ROUNDS = 4;

    // 支持服务端安全拒答兜底（fallbacks: "default"）的模型
    const FALLBACK_MODELS = ['claude-fable-5-1', 'claude-opus-5-5', 'claude-opus-5', 'claude-sonnet-5-5'];

    protected $apiKey;
    protected $model;

    public function __construct(string $apiKey, string $model)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function run(string $system, array $history, array $tools, callable $runTool): array
    {
        if (!class_exists(Client::class)) {
            throw new RuntimeException('未安装 Claude SDK，请在服务器执行 composer install');
        }

        $client = new Client(apiKey: $this->apiKey);
        $toolDefs = array_map(function ($tool) {
            return [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'inputSchema' => $tool['parameters'],
            ];
        }, $tools);
        $messages = $history;
        $result = ['content' => '', 'tools' => [], 'usage' => ['input_tokens' => 0, 'output_tokens' => 0]];

        for ($round = 0; $round <= self::MAX_TOOL_ROUNDS; $round++) {
            $response = $client->beta->messages->create(...$this->params($system, $messages, $toolDefs));
            $result['usage']['input_tokens'] += (int) ($response->usage->inputTokens ?? 0);
            $result['usage']['output_tokens'] += (int) ($response->usage->outputTokens ?? 0);

            if ($response->stopReason === 'refusal') {
                return $result;
            }

            if ($response->stopReason !== 'tool_use') {
                $texts = [];
                foreach ($response->content as $block) {
                    if ($block->type === 'text') {
                        $texts[] = $block->text;
                    }
                }
                $result['content'] = trim(implode("\n", $texts));
                return $result;
            }

            // 原样回传 assistant 内容（含 thinking 块），再附上所有工具结果
            $toolResults = [];
            foreach ($response->content as $block) {
                if ($block->type === 'tool_use') {
                    $result['tools'][] = $block->name;
                    $toolResults[] = [
                        'type' => 'tool_result',
                        'toolUseID' => $block->id,
                        'content' => json_encode($runTool($block->name, (array) $block->input), JSON_UNESCAPED_UNICODE),
                    ];
                }
            }
            $messages[] = ['role' => 'assistant', 'content' => $response->content];
            $messages[] = ['role' => 'user', 'content' => $toolResults];
        }

        return $result;
    }

    protected function params(string $system, array $messages, array $toolDefs): array
    {
        $params = [
            'model' => $this->model,
            'maxTokens' => 4000,
            'system' => $system,
            'messages' => $messages,
        ];
        if ($toolDefs) {
            $params['tools'] = $toolDefs;
        }
        // 客服问答用低 effort：回复更快、成本更低
        if (preg_match('/^claude-(opus|sonnet|haiku|fable)-5/', $this->model)) {
            $params['outputConfig'] = ['effort' => 'low'];
        }
        if (in_array($this->model, self::FALLBACK_MODELS, true)) {
            $params['fallbacks'] = 'default';
            $params['betas'] = ['server-side-fallback-2026-07-01'];
        }
        return $params;
    }
}
