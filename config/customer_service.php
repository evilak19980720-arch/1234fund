<?php

/**
 * 站内客服 / AI 客服
 * 模式、提示词、欢迎语等运营参数在后台「在线客服 → 客服设置」里修改（存 configurations 表）
 */
return [

    // DeepSeek（OpenAI 兼容接口）
    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY', ''),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'timeout' => (int) env('DEEPSEEK_TIMEOUT', 60),
    ],

    // 送给 AI 的历史消息条数
    'history_limit' => 20,

    // 单个会话每分钟最多发送消息数
    'messages_per_minute' => 10,

    // 单条消息最大长度
    'max_message_length' => 1000,

    // AI 客服处理用的队列
    'queue' => env('CS_QUEUE', 'default'),
];
