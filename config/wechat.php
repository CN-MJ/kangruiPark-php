<?php

return [

    'mini' => [
        'app_id' => env('WECHAT_MINI_APPID', ''),
        'secret' => env('WECHAT_MINI_SECRET', ''),
        // 本地无 Secret 时自动 mock；也可强制 WECHAT_MINI_MOCK=true
        'mock' => (bool) env('WECHAT_MINI_MOCK', false),
        'mock_phone' => env('WECHAT_MINI_MOCK_PHONE', '13900000001'),
    ],

];
