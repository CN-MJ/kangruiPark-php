<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\SoftDeleteFlag;

class EnterpriseService extends Model
{
    use SoftDeleteFlag;

    public const STATUS_ON = 1;

    public const STATUS_OFF = 0;

    protected $fillable = [
        'enterprise_id',
        'name',
        'summary',
        'description',
        'price_text',
        'images',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'enterprise_id' => 'integer',
            'images' => 'array',
            'status' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'enterprise_service_id');
    }

    public function scopeOnShelf($query)
    {
        return $query->where('status', self::STATUS_ON);
    }
}
