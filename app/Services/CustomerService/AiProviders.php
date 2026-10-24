<?php

namespace App\Services\CustomerService;

use App\Models\CsAiProvider;
use App\Services\CustomerService\Clients\AiClient;
use App\Services\CustomerService\Clients\AnthropicClient;
use App\Services\CustomerService\Clients\OpenAiCompatibleClient;
use RuntimeException;

/**
 * 可选的 AI 服务商。后台「客服设置」里选择服务商、填写 Key 即可切换。
 * models 只是下拉建议，后台可以填写任意模型名。
 */
class AiProviders
{
    const TYPE_OPENAI = 'openai';       // OpenAI 兼容接口（/chat/completions）
    const TYPE_ANTHROPIC = 'anthropic'; // Claude（官方 PHP SDK）

    const LIST = [
        'deepseek' => [
            'name' => 'DeepSeek 深度求索',
            'type' => self::TYPE_OPENAI,
            'base_url' => 'https://api.deepseek.com',
            'models' => ['deepseek-chat', 'deepseek-reasoner'],
            'key_url' => 'https://platform.deepseek.com/api_keys',
        ],
        'openai' => [
            'name' => 'OpenAI (GPT)',
            'type' => self::TYPE_OPENAI,
            'base_url' => 'https://api.openai.com/v1',
            'models' => ['gpt-5-mini', 'gpt-5', 'gpt-4.1-mini', 'gpt-4o-mini'],
            'key_url' => 'https://platform.openai.com/api-keys',
            'max_tokens_param' => 'max_completion_tokens',
        ],
        'anthropic' => [
            'name' => 'Anthropic Claude',
            'type' => self::TYPE_ANTHROPIC,
            'base_url' => '',
            'models' => ['claude-opus-5-5', 'claude-sonnet-5-5', 'claude-haiku-5-5'],
            'key_url' => 'https://platform.claude.com/settings/keys',
        ],
        'gemini' => [
            'name' => 'Google Gemini',
            'type' => self::TYPE_OPENAI,
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai',
            'models' => ['gemini-2.5-flash', 'gemini-2.5-pro'],
            'key_url' => 'https://aistudio.google.com/apikey',
        ],
        'qwen' => [
            'name' => '通义千问 Qwen（阿里云百炼）',
            'type' => self::TYPE_OPENAI,
            'base_url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1',
            'models' => ['qwen-plus', 'qwen-max', 'qwen-turbo'],
            'key_url' => 'https://bailian.console.aliyun.com/?apiKey=1',
            'note' => '海外服务器可把接口地址改为 https://dashscope-intl.aliyuncs.com/compatible-mode/v1',
        ],
        'zhipu' => [
            'name' => '智谱 GLM',
            'type' => self::TYPE_OPENAI,
            'base_url' => 'https://open.bigmodel.cn/api/paas/v4',
            'models' => ['glm-4.5', 'glm-4.5-air', 'glm-4-flash'],
            'key_url' => 'https://bigmodel.cn/usercenter/proj-mgmt/apikeys',
        ],
        'moonshot' => [
            'name' => 'Kimi（月之暗面）',
            'type' => self::TYPE_OPENAI,
            'base_url' => 'https://api.moonshot.cn/v1',
            'models' => ['kimi-k2-turbo-preview', 'moonshot-v1-8k'],
            'key_url' => 'https://platform.moonshot.cn/console/api-keys',
        ],
        'doubao' => [
            'name' => '豆包（火山方舟）',
            'type' => self::TYPE_OPENAI,
            'base_url' => 'https://ark.cn-beijing.volces.com/api/v3',
            'models' => ['doubao-seed-1-6-250615'],
            'key_url' => 'https://console.volcengine.com/ark/region:ark+cn-beijing/apiKey',
            'note' => '模型可填写模型名或方舟控制台里的推理接入点 ID（ep-xxxx）',
        ],
        'xai' => [
            'name' => 'xAI Grok',
            'type' => self::TYPE_OPENAI,
            'base_url' => 'https://api.x.ai/v1',
            'models' => ['grok-4', 'grok-3-mini'],
            'key_url' => 'https://console.x.ai',
        ],
        'mistral' => [
            'name' => 'Mistral',
            'type' => self::TYPE_OPENAI,
            'base_url' => 'https://api.mistral.ai/v1',
            'models' => ['mistral-small-latest', 'mistral-large-latest'],
            'key_url' => 'https://console.mistral.ai/api-keys',
        ],
        'siliconflow' => [
            'name' => '硅基流动 SiliconFlow',
            'type' => self::TYPE_OPENAI,
            'base_url' => 'https://api.siliconflow.cn/v1',
            'models' => ['deepseek-ai/DeepSeek-V3', 'Qwen/Qwen2.5-72B-Instruct'],
            'key_url' => 'https://cloud.siliconflow.cn/account/ak',
        ],
        'openrouter' => [
            'name' => 'OpenRouter（多模型聚合）',
            'type' => self::TYPE_OPENAI,
            'base_url' => 'https://openrouter.ai/api/v1',
            'models' => ['openai/gpt-4o-mini', 'google/gemini-2.5-flash', 'deepseek/deepseek-chat'],
            'key_url' => 'https://openrouter.ai/keys',
        ],
        'custom' => [
            'name' => '自定义（OpenAI 兼容接口）',
            'type' => self::TYPE_OPENAI,
            'base_url' => '',
            'models' => [],
            'key_url' => '',
            'note' => '填写兼容 OpenAI /chat/completions 的接口地址，例如自建的 one-api / new-api',
        ],
    ];

    public static function exists(string $provider): bool
    {
        return isset(self::LIST[$provider]);
    }

    /**
     * 当前启用的服务商配置：['provider', 'api_key', 'model', 'base_url']，未配置返回 null
     */
    public static function active(): ?array
    {
        $provider = (string) CsSettings::get('cs_ai_provider');

        if (self::exists($provider)) {
            try {
                $row = CsAiProvider::query()->where('provider', $provider)->first();
            } catch (\Throwable $e) {
                // 还没执行 cs_ai_providers 迁移
                $row = null;
            }
            if ($row && $row->api_key) {
                return self::resolve($provider, $row->api_key, (string) $row->model, (string) $row->base_url);
            }
        }

        // 兼容旧配置：.env 里的 DEEPSEEK_API_KEY
        $deepseek = config('customer_service.deepseek');
        if (!empty($deepseek['api_key'])) {
            return self::resolve('deepseek', $deepseek['api_key'], (string) $deepseek['model'], (string) $deepseek['base_url']);
        }

        return null;
    }

    /**
     * 补全默认的模型和接口地址
     */
    public static function resolve(string $provider, string $apiKey, string $model, string $baseUrl): array
    {
        $meta = self::LIST[$provider];

        return [
            'provider' => $provider,
            'api_key' => $apiKey,
            'model' => $model !== '' ? $model : ($meta['models'][0] ?? ''),
            'base_url' => $baseUrl !== '' ? $baseUrl : $meta['base_url'],
        ];
    }

    public static function client(array $config): AiClient
    {
        $meta = self::LIST[$config['provider']] ?? null;
        if (!$meta) {
            throw new RuntimeException('未知的 AI 服务商：' . $config['provider']);
        }
        if ($config['model'] === '') {
            throw new RuntimeException('请填写模型名称');
        }

        if ($meta['type'] === self::TYPE_ANTHROPIC) {
            return new AnthropicClient($config['api_key'], $config['model']);
        }

        if ($config['base_url'] === '') {
            throw new RuntimeException('请填写接口地址');
        }

        return new OpenAiCompatibleClient($config['api_key'], $config['model'], $config['base_url'], $meta['max_tokens_param'] ?? 'max_tokens');
    }

    /**
     * Key 脱敏显示
     */
    public static function maskKey(?string $key): string
    {
        $key = (string) $key;
        if ($key === '') {
            return '';
        }
        return strlen($key) <= 8 ? '****' : substr($key, 0, 3) . '****' . substr($key, -4);
    }
}
