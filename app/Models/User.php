<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $table = 'xcx_users';

    public const ROLE_VISITOR = 'visitor';

    public const ROLE_MERCHANT = 'merchant';

    public const ROLE_PARK = 'park';

    protected $fillable = [
        'phone',
        'name',
        'role',
        'checked_in_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(UserToken::class);
    }

    public function isPark(): bool
    {
        return $this->role === self::ROLE_PARK;
    }

    public function isMerchant(): bool
    {
        return $this->role === self::ROLE_MERCHANT;
    }
}
