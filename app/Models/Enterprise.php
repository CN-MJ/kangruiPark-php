<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Enterprise extends Model
{
    public const STATUS_ON = 1;

    public const STATUS_OFF = 0;

    public const AUDIENCE_C = 'c';

    public const AUDIENCE_B = 'b';

    public const AUDIENCE_BC = 'bc';

    public const SOURCE_COMMUNITY = 'community';

    public const SOURCE_SELF = 'self';

    protected $fillable = [
        'name',
        'contact_name',
        'contact_phone',
        'primary_category_id',
        'business_intro',
        'address',
        'latitude',
        'longitude',
        'map_enabled',
        'service_audience',
        'source',
        'settle_in_park',
        'images',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'primary_category_id' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'map_enabled' => 'boolean',
            'settle_in_park' => 'boolean',
            'images' => 'array',
            'status' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(EnterpriseCategory::class, 'primary_category_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            EnterpriseCategory::class,
            'enterprise_category',
            'enterprise_id',
            'enterprise_category_id'
        )->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOnShelf(): bool
    {
        return (int) $this->status === self::STATUS_ON;
    }

    public static function audienceText(string $value): string
    {
        return match ($value) {
            self::AUDIENCE_C => '面向百姓C端',
            self::AUDIENCE_B => '面向企业B端',
            default => 'BC兼顾',
        };
    }

    public static function sourceText(string $value): string
    {
        return match ($value) {
            self::SOURCE_SELF => '自主入驻',
            default => '健康共同体入库',
        };
    }
}
