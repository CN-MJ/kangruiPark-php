<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingRecord extends Model
{
    /** 状态：未离场 */
    public const STATUS_IN_PARK = 0;

    /** 状态：已离场 */
    public const STATUS_LEFT = 1;

    public const SOURCE_MANUAL = 0;

    public const SOURCE_HARDWARE = 1;

    protected $fillable = [
        'plate_number',
        'plate_key',
        'active_plate_key',
        'entry_time',
        'exit_time',
        'status',
        'source_type',
        'event_id',
        'entry_device_id',
        'exit_device_id',
    ];

    protected function casts(): array
    {
        return [
            'entry_time' => 'datetime',
            'exit_time' => 'datetime',
            'status' => 'integer',
            'source_type' => 'integer',
            'event_id' => 'integer',
            'entry_device_id' => 'integer',
            'exit_device_id' => 'integer',
        ];
    }

    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ParkingEvent::class, 'event_id');
    }

    public function entryDevice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ParkingDevice::class, 'entry_device_id');
    }

    public function exitDevice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ParkingDevice::class, 'exit_device_id');
    }

    /**
     * 标准化车牌：去空格、转大写
     */
    public static function normalizePlate(string $plate): string
    {
        return strtoupper(preg_replace('/\s+/u', '', trim($plate)));
    }

    public function scopeInParkForPlate($query, string $plate)
    {
        return $query
            ->where('plate_key', static::normalizePlate($plate))
            ->where('status', self::STATUS_IN_PARK);
    }

    public function markExited(?\DateTimeInterface $exitTime = null): void
    {
        $this->update([
            'exit_time' => $exitTime ?? now(),
            'status' => self::STATUS_LEFT,
            'active_plate_key' => null,
        ]);
    }
}
