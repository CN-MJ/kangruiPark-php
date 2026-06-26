<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentIntent extends Model
{
    public const STATUS_PENDING = 0;
    public const STATUS_CONTACTED = 1;
    public const STATUS_HIGH_INTENT = 2;
    public const STATUS_DEAL = 3;
    public const STATUS_INVALID = 4;

    protected $fillable = [
        'company_name',
        'contact_name',
        'contact_phone',
        'demand_area',
        'house_id',
        'message',
        'status',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'house_id' => 'integer',
            'status' => 'integer',
        ];
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(BuildingHouse::class, 'house_id');
    }
}
