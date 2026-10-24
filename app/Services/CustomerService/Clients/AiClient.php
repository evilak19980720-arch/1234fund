<?php

namespace App\Services\CustomerService\Clients;

/**
 * AI 对话客户端：各服务商自行处理消息格式和工具调用循环
 */
interface AiClient
{
    /**
     * @param string   $system  系统提示词
     * @param array    $history [['role' => 'user'|'assistant', 'content' => string], ...]
     * @param array    $tools   [['name' => ..., 'description' => ..., 'parameters' => JSON Schema], ...]
     * @param callable $runTool fn(string $name, array $args): array  执行工具并返回结果
     * @return array{content: string, tools: string[], usage: array{input_tokens: int, output_tokens: int}}
     */
    public function run(string $system, array $history, array $tools, callable $runTool): array;
}
