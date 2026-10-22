<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\SoftDeleteFlag;

class UserVehicle extends Model
{
    use SoftDeleteFlag;

    public const STATUS_ACTIVE = 1;

    public const STATUS_INACTIVE = 0;

    protected $fillable = [
        'user_id',
        'plate_number',
        'is_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_default' => 'boolean',
            'status' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function normalizePlate(string $plate): string
    {
        return ParkingRecord::normalizePlate($plate);
    }
}
