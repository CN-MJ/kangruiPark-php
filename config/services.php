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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 微信服务号（公众号）
    |--------------------------------------------------------------------------
    | 用于网页授权获取 mp_openid，以及预约相关模板消息推送。
    */
    'wechat_mp' => [
        'app_id' => env('WECHAT_MP_APPID'),
        'secret' => env('WECHAT_MP_SECRET'),
        'auth_redirect_uri' => env('WECHAT_MP_AUTH_REDIRECT_URI'),
        'templates' => [
            'to_merchant' => env('WECHAT_MP_TPL_TO_MERCHANT', 'TEST_TPL_TO_MERCHANT'),
            'to_visitor' => env('WECHAT_MP_TPL_TO_VISITOR', 'TEST_TPL_TO_VISITOR'),
        ],
    ],

];
