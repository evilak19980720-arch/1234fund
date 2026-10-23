<?php

namespace App\Services\CustomerService;

use App\Handlers\KeysHandler;
use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;

/**
 * 客服设置：存放在 configurations 表，后台「客服设置」页面修改
 */
class CsSettings
{
    const MODE_INTERNAL = 'internal'; // 本站在线客服
    const MODE_EXTERNAL = 'external'; // 第三方客服链接

    // key => [类型, 默认值, 描述]
    const ITEMS = [
        'cs_mode' => ['string', self::MODE_EXTERNAL, '客服模式 internal:本站客服 external:第三方客服'],
        'customer_service_url' => ['string', '', '第三方客服链接'],
        'cs_ai_enabled' => ['bool', '1', 'AI客服开关'],
        'cs_ai_prompt' => ['text', '', 'AI客服提示词（业务知识、语气、话术）'],
        'cs_welcome' => ['text', 'Hello! I am the AI customer service assistant. How can I help you?', '客服欢迎语'],
        'cs_ai_daily_limit' => ['int', '50', '单个用户每天AI回复次数上限'],
        'cs_offline_notice' => ['text', '', '转人工后的提示（如人工客服工作时间）'],
    ];

    public static function get(string $key)
    {
        $value = Configuration::getItem($key);
        if ($value === null) {
            return self::ITEMS[$key][1] ?? null;
        }
        return $value;
    }

    public static function mode(): string
    {
        return self::get('cs_mode') === self::MODE_INTERNAL ? self::MODE_INTERNAL : self::MODE_EXTERNAL;
    }

    public static function aiEnabled(): bool
    {
        return (string) self::get('cs_ai_enabled') === '1' && config('customer_service.deepseek.api_key') !== '';
    }

    public static function save(array $values): void
    {
        foreach (self::ITEMS as $key => [$type, $default, $describe]) {
            if (!array_key_exists($key, $values)) {
                continue;
            }
            $config = Configuration::query()->where('key', $key)->first() ?: new Configuration();
            $config->key = $key;
            $config->type = $config->type ?: $type;
            $config->describe = $config->describe ?: $describe;
            $config->value = (string) ($values[$key] ?? '');
            $config->save();
        }
        Cache::forget(KeysHandler::CONFIGURATION);
    }
}
