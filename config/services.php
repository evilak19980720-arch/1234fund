<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // 以下密钥原先写死在代码里，改为从 .env 读取
    'onepay' => [
        'aes_key' => env('ONEPAY_AES_KEY'),
    ],

    'onepay_in' => [
        'aes_key' => env('ONEPAY_IN_AES_KEY'),
    ],

    'nodereal' => [
        'api_key' => env('NODEREAL_API_KEY'),
    ],

    'k780' => [
        'appkey' => env('K780_APPKEY'),
        'sign' => env('K780_SIGN'),
    ],

    'bnb_transfer' => [
        'private_key' => env('BNB_TRANSFER_PRIVATE_KEY'),
    ],

];
