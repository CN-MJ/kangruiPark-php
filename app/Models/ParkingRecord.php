<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingRecord extends Model
{
    /** 状态：未离场 */
    public const STATUS_IN_PARK = 0;

    /** 状态：已离场 */
    public const STATUS_LEFT = 1;

    protected $fillable = [
        'plate_number',
        'plate_key',
        'active_plate_key',
        'entry_time',
        'exit_time',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'entry_time' => 'datetime',
            'exit_time' => 'datetime',
            'status' => 'integer',
        ];
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
