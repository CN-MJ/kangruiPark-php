<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\SoftDeleteFlag;

class ParkingDevice extends Model
{
    use SoftDeleteFlag;

    public const TYPE_ENTRY_CAMERA = 1;

    public const TYPE_EXIT_CAMERA = 2;

    public const TYPE_BARRIER = 3;

    public const DIRECTION_ENTRY = 1;

    public const DIRECTION_EXIT = 2;

    public const STATUS_ACTIVE = 1;

    public const STATUS_INACTIVE = 0;

    protected $fillable = [
        'device_name',
        'device_type',
        'direction',
        'location',
        'api_key',
        'status',
        'last_heartbeat_at',
    ];

    protected function casts(): array
    {
        return [
            'device_type' => 'integer',
            'direction' => 'integer',
            'status' => 'integer',
            'last_heartbeat_at' => 'datetime',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(ParkingEvent::class, 'device_id');
    }

    public function isActive(): bool
    {
        return (int) $this->status === self::STATUS_ACTIVE;
    }
}
