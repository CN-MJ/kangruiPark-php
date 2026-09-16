<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantToken;
use App\Models\SuperToken;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Database\Eloquent\Builder;

class AuthTokenService
{
    public function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public function issueUserToken(User $user): string
    {
        if (config('auth_security.token.revoke_previous_on_login', true)) {
            $user->tokens()->delete();
        }

        $raw = $this->newRawToken();
        $ttlDays = (int) config('auth_security.token.ttl_days', 7);
        if ($user->isPark()) {
            $ttlDays = (int) config('auth_security.token.park_ttl_days', $ttlDays);
        }

        $user->tokens()->create([
            'token' => $this->hashToken($raw),
            'expires_at' => now()->addDays(max(1, $ttlDays)),
        ]);

        return $raw;
    }

    public function issueMerchantToken(Merchant $merchant): string
    {
        if (config('auth_security.token.revoke_previous_on_login', true)) {
            $merchant->tokens()->delete();
        }

        $raw = $this->newRawToken();
        $merchant->tokens()->create([
            'token' => $this->hashToken($raw),
            'expires_at' => now()->addDays(max(1, (int) config('auth_security.token.ttl_days', 7))),
        ]);

        return $raw;
    }

    public function issueSuperToken(User $user): string
    {
        $user->superTokens()->delete();

        $raw = $this->newRawToken();
        $ttlHours = (int) config('auth_security.super.token_ttl_hours', 4);
        $user->superTokens()->create([
            'token' => $this->hashToken($raw),
            'expires_at' => now()->addHours(max(1, $ttlHours)),
        ]);

        return $raw;
    }

    public function findUserToken(string $rawToken): ?UserToken
    {
        $row = $this->matchTokenQuery(UserToken::query(), $rawToken)
            ->where('expires_at', '>', now())
            ->with('user')
            ->first();

        if ($row) {
            $this->lazyUpgradeHash($row, $rawToken);
        }

        return $row;
    }

    public function findMerchantToken(string $rawToken): ?MerchantToken
    {
        $row = $this->matchTokenQuery(MerchantToken::query(), $rawToken)
            ->where('expires_at', '>', now())
            ->with('merchant')
            ->first();

        if ($row) {
            $this->lazyUpgradeHash($row, $rawToken);
        }

        return $row;
    }

    public function findSuperToken(string $rawToken): ?SuperToken
    {
        $row = $this->matchTokenQuery(SuperToken::query(), $rawToken)
            ->where('expires_at', '>', now())
            ->with('user')
            ->first();

        if ($row) {
            $this->lazyUpgradeHash($row, $rawToken);
        }

        return $row;
    }

    public function revokeUserToken(?string $rawToken): void
    {
        if (! $rawToken) {
            return;
        }
        $this->matchTokenQuery(UserToken::query(), $rawToken)->delete();
    }

    public function revokeMerchantToken(?string $rawToken): void
    {
        if (! $rawToken) {
            return;
        }
        $this->matchTokenQuery(MerchantToken::query(), $rawToken)->delete();
    }

    public function revokeSuperToken(?string $rawToken): void
    {
        if (! $rawToken) {
            return;
        }
        $this->matchTokenQuery(SuperToken::query(), $rawToken)->delete();
    }

    /**
     * 改密后吊销该用户其它会话，保留当前 Bearer（若有）。
     */
    public function revokeOtherUserTokens(User $user, ?string $keepRawToken = null): void
    {
        $query = $user->tokens();
        if ($keepRawToken) {
            $hash = $this->hashToken($keepRawToken);
            $query->where(function ($q) use ($keepRawToken, $hash) {
                $q->where('token', '!=', $keepRawToken)->where('token', '!=', $hash);
            });
        }
        $query->delete();
        $user->superTokens()->delete();
    }

    public function revokeOtherMerchantTokens(Merchant $merchant, ?string $keepRawToken = null): void
    {
        $query = $merchant->tokens();
        if ($keepRawToken) {
            $hash = $this->hashToken($keepRawToken);
            $query->where(function ($q) use ($keepRawToken, $hash) {
                $q->where('token', '!=', $keepRawToken)->where('token', '!=', $hash);
            });
        }
        $query->delete();
    }

    private function newRawToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function matchTokenQuery(Builder $query, string $rawToken): Builder
    {
        $hash = $this->hashToken($rawToken);

        return $query->where(function ($q) use ($rawToken, $hash) {
            $q->where('token', $rawToken)->orWhere('token', $hash);
        });
    }

    private function lazyUpgradeHash(UserToken|MerchantToken|SuperToken $row, string $rawToken): void
    {
        $hash = $this->hashToken($rawToken);
        if ($row->token === $hash) {
            return;
        }
        // 明文过渡期：命中后升级为哈希
        if ($row->token === $rawToken) {
            $row->forceFill(['token' => $hash])->save();
        }
    }
}
