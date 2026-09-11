<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingEvent extends Model
{
    public const DIRECTION_ENTRY = 1;

    public const DIRECTION_EXIT = 2;

    public const STATUS_PENDING = 0;

    public const STATUS_PROCESSED = 1;

    public const STATUS_IGNORED = 2;

    public const STATUS_ERROR = 3;

    protected $fillable = [
        'device_id',
        'plate_number',
        'direction',
        'recognition_time',
        'image_url',
        'confidence',
        'event_id',
        'processed_status',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'device_id' => 'integer',
            'direction' => 'integer',
            'recognition_time' => 'datetime',
            'confidence' => 'float',
            'processed_status' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(ParkingDevice::class, 'device_id');
    }

    public function parkingRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ParkingRecord::class, 'event_id');
    }

    public function isPending(): bool
    {
        return (int) $this->processed_status === self::STATUS_PENDING;
    }

    public function markProcessed(): void
    {
        $this->update([
            'processed_status' => self::STATUS_PROCESSED,
            'processed_at' => now(),
        ]);
    }

    public function markError(): void
    {
        $this->update([
            'processed_status' => self::STATUS_ERROR,
            'processed_at' => now(),
        ]);
    }
}
