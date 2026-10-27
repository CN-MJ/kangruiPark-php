<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\SoftDeleteFlag;

class Appointment extends Model
{
    use SoftDeleteFlag;

    /** 状态：待确认 */
    public const STATUS_PENDING = 0;

    /** 状态：已确认 */
    public const STATUS_CONFIRMED = 1;

    /** 状态：已拒绝 */
    public const STATUS_REJECTED = 2;

    /** 状态：已取消 */
    public const STATUS_CANCELLED = 3;

    protected $fillable = [
        'user_name',
        'user_phone',
        'appointment_time',
        'resource_name',
        'merchant_id',
        'enterprise_id',
        'enterprise_service_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'appointment_time' => 'datetime',
            'status' => 'integer',
            'merchant_id' => 'integer',
            'enterprise_id' => 'integer',
            'enterprise_service_id' => 'integer',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function enterpriseService(): BelongsTo
    {
        return $this->belongsTo(EnterpriseService::class, 'enterprise_service_id');
    }

    public function statusText(): string
    {
        return match ($this->status) {
            self::STATUS_CONFIRMED => '已确认',
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_CANCELLED => '已取消',
            default => '待确认',
        };
    }
}
