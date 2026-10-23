<?php
return [
    'kmiCloud' => [
        'access_key' => env('KMI_CLOUD_ACCESS_KEY'),
        'secret_key' => env('KMI_CLOUD_SECRET_KEY'),
        //有效时间，默认五分钟
        'valid_minutes' => env('kmi_cloud_valid_minutes',5),
        'from' => env('KMI_CLOUD_ACCESS_FROM','RSHLVV'),
        //模板
        'message' => '[RSHLVV] Your code:%s'
    ],

    'nodeSms' => [
        'account' => env('NODE_SMS_ACCOUNT',''),
        'password' => env('NODE_SMS_PASSWORD','')
    ],

    'xqtxhn' => [
        'account' => env('XQTXHN_SMS_ACCOUNT',''),
        'password' => env('XQTXHN_SMS_PASSWORD','')
    ],

    'easySms' => [
        // HTTP 请求的超时时间（秒）
        'timeout' => 10.0,

        // 默认发送配置
        'default' => [
            // 网关调用策略，默认：顺序调用
            'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

            // 默认可用的发送网关
            'gateways' => [
                'chuanglan',
            ],
        ],
        // 可用的网关配置
        'gateways' => [
            'errorlog' => [
                'file' => '/tmp/EASY-SMS.LOG',
            ],
            'chuanglan' => [
                'account' => env('SMS_253_ACCOUNT'),
                'password' => env('SMS_253_PASSWORD'),
                'intel_account' => env('INTEL_SMS_253_ACCOUNT'),
                'intel_password' => env('INTEL_SMS_253_PASSWORD'),
                'channel'  => \Overtrue\EasySms\Gateways\ChuanglanGateway::CHANNEL_PROMOTION_CODE,
                'sign' => env('SMS_253_SIGN'),
                'unsubscribe' => env('SMS_253_UNSUBSCRIBE'),
                'templates' => [
                    'register' => env('SMS_TEMPLATE_REGISTER'),
                ]
            ],
        ],
    ],
];
