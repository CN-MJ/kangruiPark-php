<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'xcx_users';

    public const ROLE_VISITOR = 'visitor';

    public const ROLE_MERCHANT = 'merchant';

    public const ROLE_PARK = 'park';

    protected $fillable = [
        'phone',
        'name',
        'role',
        'park_role',
        'password',
        'openid',
        'phone_verified_at',
        'can_escalate',
        'checked_in_at',
        'merchant_apply_status',
        'merchant_applied_at',
    ];

    public const APPLY_NONE = 'none';

    public const APPLY_PENDING = 'pending';

    public const APPLY_APPROVED = 'approved';

    public const APPLY_REJECTED = 'rejected';

    protected $hidden = [
        'password',
        'can_escalate',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'merchant_applied_at' => 'datetime',
            'can_escalate' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(UserToken::class);
    }

    public function superTokens(): HasMany
    {
        return $this->hasMany(SuperToken::class);
    }

    public function canEscalate(): bool
    {
        return (bool) $this->can_escalate;
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(UserVehicle::class);
    }

    public function isPark(): bool
    {
        return $this->role === self::ROLE_PARK;
    }

    public function isMerchant(): bool
    {
        return $this->role === self::ROLE_MERCHANT;
    }

    public function parkRoleKey(): string
    {
        return \App\Support\ParkPermissions::normalizeRole($this->park_role);
    }

    public function hasParkPermission(string $permission): bool
    {
        return \App\Support\ParkPermissions::allows($this, $permission);
    }
}
