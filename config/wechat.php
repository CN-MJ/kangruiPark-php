<?php

return [

    'mini' => [
        'app_id' => env('WECHAT_MINI_APPID', ''),
        'secret' => env('WECHAT_MINI_SECRET', ''),
    // 本地无 Secret 时仅 local 自动 mock；生产环境代码层强制禁止
    // 也可显式 WECHAT_MINI_MOCK=true（非 production）
    'mock' => (bool) env('WECHAT_MINI_MOCK', false),
        'mock_phone' => env('WECHAT_MINI_MOCK_PHONE', '13900000001'),
    ],

];
