<?php

return [

  /*
  |--------------------------------------------------------------------------
  | 短信验证码（预留配置，接入云厂商后修改 driver）
  |--------------------------------------------------------------------------
  |
  | driver:
  |   - log     开发环境，验证码写入日志，不真实发送
  |   - aliyun  阿里云短信（待接入）
  |   - tencent 腾讯云短信（待接入）
  |
  */

    'enabled' => (bool) env('SMS_ENABLED', false),

    'driver' => env('SMS_DRIVER', 'log'),

    'code_length' => (int) env('SMS_CODE_LENGTH', 6),

    'code_ttl_minutes' => (int) env('SMS_CODE_TTL_MINUTES', 5),

    'resend_interval_seconds' => (int) env('SMS_RESEND_INTERVAL', 60),

    'daily_limit_per_phone' => (int) env('SMS_DAILY_LIMIT_PER_PHONE', 10),

    'template_code' => env('SMS_TEMPLATE_CODE', ''),

    'sign_name' => env('SMS_SIGN_NAME', '赣南健康城'),

    'aliyun' => [
        'access_key_id' => env('ALIYUN_SMS_ACCESS_KEY_ID'),
        'access_key_secret' => env('ALIYUN_SMS_ACCESS_KEY_SECRET'),
        'region' => env('ALIYUN_SMS_REGION', 'cn-hangzhou'),
    ],

    'tencent' => [
        'secret_id' => env('TENCENT_SMS_SECRET_ID'),
        'secret_key' => env('TENCENT_SMS_SECRET_KEY'),
        'sdk_app_id' => env('TENCENT_SMS_SDK_APP_ID'),
    ],

];
