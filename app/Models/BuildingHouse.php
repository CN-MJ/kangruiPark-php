<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuildingHouse extends Model
{
    public const STATUS_ON_MARKET = 1;

    public const STATUS_OFF_MARKET = 0;

    protected $fillable = [
        'title',
        'area',
        'price',
        'house_type',
        'description',
        'images',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'status' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function intents(): HasMany
    {
        return $this->hasMany(InvestmentIntent::class, 'house_id');
    }
}
