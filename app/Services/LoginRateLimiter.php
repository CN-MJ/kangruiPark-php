<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

class LoginRateLimiter
{
    public function key(string $scope, string $phone, ?string $ip = null): string
    {
        return sprintf('login:%s:%s:%s', $scope, $phone, $ip ?: 'unknown');
    }

    public function tooManyAttempts(string $key): bool
    {
        $max = (int) config('auth_security.login.max_attempts', 5);

        return RateLimiter::tooManyAttempts($key, $max);
    }

    public function hit(string $key): void
    {
        $decay = (int) config('auth_security.login.decay_minutes', 15) * 60;
        RateLimiter::hit($key, $decay);
    }

    public function clear(string $key): void
    {
        RateLimiter::clear($key);
    }

    public function availableIn(string $key): int
    {
        return RateLimiter::availableIn($key);
    }
}
