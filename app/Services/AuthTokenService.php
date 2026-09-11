<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantToken;
use App\Models\User;
use App\Models\UserToken;

class AuthTokenService
{
    public function issueUserToken(User $user): string
    {
        if (config('auth_security.token.revoke_previous_on_login', true)) {
            $user->tokens()->delete();
        }

        $token = bin2hex(random_bytes(32));
        $user->tokens()->create([
            'token' => $token,
            'expires_at' => now()->addDays((int) config('auth_security.token.ttl_days', 7)),
        ]);

        return $token;
    }

    public function issueMerchantToken(Merchant $merchant): string
    {
        if (config('auth_security.token.revoke_previous_on_login', true)) {
            $merchant->tokens()->delete();
        }

        $token = bin2hex(random_bytes(32));
        $merchant->tokens()->create([
            'token' => $token,
            'expires_at' => now()->addDays((int) config('auth_security.token.ttl_days', 7)),
        ]);

        return $token;
    }

    public function revokeUserToken(?string $token): void
    {
        if ($token) {
            UserToken::query()->where('token', $token)->delete();
        }
    }

    public function revokeMerchantToken(?string $token): void
    {
        if ($token) {
            MerchantToken::query()->where('token', $token)->delete();
        }
    }

    public function issueSuperToken(User $user): string
    {
        $user->superTokens()->delete();

        $token = bin2hex(random_bytes(32));
        $ttlHours = (int) config('auth_security.super.token_ttl_hours', 4);
        $user->superTokens()->create([
            'token' => $token,
            'expires_at' => now()->addHours(max(1, $ttlHours)),
        ]);

        return $token;
    }

    public function revokeSuperToken(?string $token): void
    {
        if ($token) {
            \App\Models\SuperToken::query()->where('token', $token)->delete();
        }
    }
}
