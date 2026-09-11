<?php

return [

    'password' => [
        'min_length' => (int) env('AUTH_PASSWORD_MIN_LENGTH', 6),
        'require_for_park' => (bool) env('AUTH_REQUIRE_PASSWORD_PARK', true),
        'require_for_merchant' => (bool) env('AUTH_REQUIRE_PASSWORD_MERCHANT', true),
    ],

    'token' => [
        'ttl_days' => (int) env('AUTH_TOKEN_TTL_DAYS', 7),
        'revoke_previous_on_login' => (bool) env('AUTH_REVOKE_PREVIOUS_TOKENS', true),
    ],

    'login' => [
        'max_attempts' => (int) env('AUTH_LOGIN_MAX_ATTEMPTS', 5),
        'decay_minutes' => (int) env('AUTH_LOGIN_DECAY_MINUTES', 15),
    ],

    'captcha' => [
        'ttl_seconds' => (int) env('AUTH_CAPTCHA_TTL_SECONDS', 300),
    ],

    'super' => [
        'enabled' => (bool) env('SUPER_CONSOLE_ENABLED', true),
        'token_ttl_hours' => (int) env('SUPER_TOKEN_TTL_HOURS', 4),
        'ip_allowlist' => env('SUPER_ADMIN_IP_ALLOWLIST', ''),
    ],

];
