<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\SoftDeleteFlag;

class BuildingHouse extends Model
{
    use SoftDeleteFlag;

    public const STATUS_ON_MARKET = 1;

    public const STATUS_OFF_MARKET = 0;

    /** 未租未售 */
    public const LEASE_AVAILABLE = 'available';

    /** 已租已售 */
    public const LEASE_RENTED_SOLD = 'rented_sold';

    /** 已租未售 */
    public const LEASE_RENTED_UNSOLD = 'rented_unsold';

    /** 未租已售 */
    public const LEASE_SOLD_UNRENTED = 'sold_unrented';

    public const LEASE_SALE_STATUSES = [
        self::LEASE_AVAILABLE,
        self::LEASE_RENTED_SOLD,
        self::LEASE_RENTED_UNSOLD,
        self::LEASE_SOLD_UNRENTED,
    ];

    public const LEASE_SALE_LABELS = [
        self::LEASE_AVAILABLE => '未租未售',
        self::LEASE_RENTED_SOLD => '已租已售',
        self::LEASE_RENTED_UNSOLD => '已租未售',
        self::LEASE_SOLD_UNRENTED => '未租已售',
    ];

    /** 前端色调：blue / red / yellow / gray */
    public const LEASE_SALE_TONES = [
        self::LEASE_AVAILABLE => 'blue',
        self::LEASE_RENTED_SOLD => 'red',
        self::LEASE_RENTED_UNSOLD => 'yellow',
        self::LEASE_SOLD_UNRENTED => 'gray',
    ];

    protected $fillable = [
        'title',
        'building_no',
        'floor',
        'floor_height',
        'room_no',
        'building_area',
        'lease_sale_status',
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
            'floor' => 'integer',
            'floor_height' => 'float',
            'building_area' => 'float',
            'images' => 'array',
            'status' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function intents(): HasMany
    {
        return $this->hasMany(InvestmentIntent::class, 'house_id');
    }

    public function leaseSaleText(): string
    {
        return self::LEASE_SALE_LABELS[$this->lease_sale_status] ?? '未租未售';
    }

    public function leaseSaleTone(): string
    {
        return self::LEASE_SALE_TONES[$this->lease_sale_status] ?? 'blue';
    }

    public function floorLabel(): string
    {
        if ($this->floor === null) {
            return '';
        }

        $floor = (int) $this->floor;
        if ($floor === -1) {
            return '负一层';
        }
        if ($floor < 0) {
            return '负'.abs($floor).'层';
        }

        return $floor.'层';
    }

    /**
     * 根据结构化字段生成展示标题与兼容 area。
     */
    public static function syncDerivedFields(array $data): array
    {
        $buildingNo = isset($data['building_no']) ? trim((string) $data['building_no']) : '';
        $roomNo = isset($data['room_no']) ? trim((string) $data['room_no']) : '';
        if ($buildingNo !== '' && $roomNo !== '') {
            $data['title'] = $buildingNo.'栋 '.$roomNo;
        }

        if (array_key_exists('building_area', $data) && $data['building_area'] !== null && $data['building_area'] !== '') {
            $area = round((float) $data['building_area'], 2);
            $data['building_area'] = $area;
            $data['area'] = rtrim(rtrim(number_format($area, 2, '.', ''), '0'), '.').'㎡';
        }

        return $data;
    }
}
