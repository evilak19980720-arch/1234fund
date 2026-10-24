<?php

/**
 * 站内客服 / AI 客服
 * 模式、AI 服务商与 Key、提示词、欢迎语等都在后台「在线客服 → 客服设置」里修改
 */
return [

    // 兼容旧配置：后台还没配置 AI 服务商时，使用 .env 里的 DeepSeek Key
    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY', ''),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
    ],

    // 送给 AI 的历史消息条数
    'history_limit' => 20,

    // 单个会话每分钟最多发送消息数
    'messages_per_minute' => 10,

    // 单条消息最大长度
    'max_message_length' => 1000,
];
