<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\SoftDeleteFlag;

class Merchant extends Model
{
    use SoftDeleteFlag;

    public const STATUS_ACTIVE = 1;

    public const STATUS_INACTIVE = 0;

    protected $fillable = [
        'enterprise_id',
        'name',
        'contact_name',
        'contact_phone',
        'mp_openid',
        'password',
        'location',
        'status',
        'sort_order',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'enterprise_id' => 'integer',
            'status' => 'integer',
            'sort_order' => 'integer',
            'password' => 'hashed',
        ];
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(MerchantToken::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
