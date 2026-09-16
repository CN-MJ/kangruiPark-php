<?php

namespace App\Support;

/**
 * 赣州市 18 个县（市、区）及根据地址/坐标的归属解析。
 */
class GanzhouDistrict
{
    /** @var list<string> */
    public const NAMES = [
        '章贡区',
        '南康区',
        '赣县区',
        '信丰县',
        '大余县',
        '上犹县',
        '崇义县',
        '安远县',
        '龙南市',
        '定南县',
        '全南县',
        '宁都县',
        '于都县',
        '兴国县',
        '会昌县',
        '寻乌县',
        '石城县',
        '瑞金市',
    ];

    /**
     * 地址别名 → 正式县区名（按匹配长度优先，长词在前）。
     *
     * @var array<string, string>
     */
    private const ADDRESS_ALIASES = [
        '章贡区' => '章贡区',
        '南康区' => '南康区',
        '赣县区' => '赣县区',
        '信丰县' => '信丰县',
        '大余县' => '大余县',
        '上犹县' => '上犹县',
        '崇义县' => '崇义县',
        '安远县' => '安远县',
        '龙南市' => '龙南市',
        '龙南县' => '龙南市',
        '定南县' => '定南县',
        '全南县' => '全南县',
        '宁都县' => '宁都县',
        '于都县' => '于都县',
        '兴国县' => '兴国县',
        '会昌县' => '会昌县',
        '寻乌县' => '寻乌县',
        '石城县' => '石城县',
        '瑞金市' => '瑞金市',
        '瑞金县' => '瑞金市',
        // 常见简称 / 功能区（落在中心城区章贡一带）
        '章贡' => '章贡区',
        '南康' => '南康区',
        '赣县' => '赣县区',
        '信丰' => '信丰县',
        '大余' => '大余县',
        '上犹' => '上犹县',
        '崇义' => '崇义县',
        '安远' => '安远县',
        '龙南' => '龙南市',
        '定南' => '定南县',
        '全南' => '全南县',
        '宁都' => '宁都县',
        '于都' => '于都县',
        '兴国' => '兴国县',
        '会昌' => '会昌县',
        '寻乌' => '寻乌县',
        '石城' => '石城县',
        '瑞金' => '瑞金市',
        '经开区' => '章贡区',
        '经济技术开发区' => '章贡区',
        '高新区' => '章贡区',
        '高新技术产业开发区' => '章贡区',
        '赣南健康城' => '章贡区',
        '健康城' => '章贡区',
        '水西' => '章贡区',
    ];

    /**
     * 各县区近似中心点（用于坐标就近归属，单位：度）。
     *
     * @var array<string, array{lat: float, lng: float}>
     */
    private const CENTERS = [
        '章贡区' => ['lat' => 25.8312, 'lng' => 114.9336],
        '南康区' => ['lat' => 25.6617, 'lng' => 114.7650],
        '赣县区' => ['lat' => 25.8607, 'lng' => 115.0116],
        '信丰县' => ['lat' => 25.3862, 'lng' => 114.9229],
        '大余县' => ['lat' => 25.4020, 'lng' => 114.3622],
        '上犹县' => ['lat' => 25.7943, 'lng' => 114.5515],
        '崇义县' => ['lat' => 25.6816, 'lng' => 114.3083],
        '安远县' => ['lat' => 25.1369, 'lng' => 115.3939],
        '龙南市' => ['lat' => 24.9111, 'lng' => 114.7899],
        '定南县' => ['lat' => 24.7842, 'lng' => 115.0278],
        '全南县' => ['lat' => 24.7424, 'lng' => 114.5302],
        '宁都县' => ['lat' => 26.4702, 'lng' => 116.0094],
        '于都县' => ['lat' => 25.9521, 'lng' => 115.4154],
        '兴国县' => ['lat' => 26.3378, 'lng' => 115.3632],
        '会昌县' => ['lat' => 25.6007, 'lng' => 115.7861],
        '寻乌县' => ['lat' => 24.9634, 'lng' => 115.6515],
        '石城县' => ['lat' => 26.3266, 'lng' => 116.3423],
        '瑞金市' => ['lat' => 25.8856, 'lng' => 116.0271],
    ];

    /** 距最近中心超过该距离（公里）则不归属 */
    private const MAX_CENTER_KM = 55.0;

    public static function isValid(?string $name): bool
    {
        return $name !== null && $name !== '' && in_array($name, self::NAMES, true);
    }

    /**
     * @return list<array{name: string}>
     */
    public static function options(): array
    {
        return array_map(static fn (string $name) => ['name' => $name], self::NAMES);
    }

    public static function resolve(?string $address, mixed $latitude = null, mixed $longitude = null): ?string
    {
        $fromAddress = self::resolveFromAddress($address);
        if ($fromAddress !== null) {
            return $fromAddress;
        }

        return self::resolveFromCoords($latitude, $longitude);
    }

    public static function resolveFromAddress(?string $address): ?string
    {
        $text = trim((string) $address);
        if ($text === '') {
            return null;
        }

        $aliases = self::ADDRESS_ALIASES;
        uksort($aliases, static fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($aliases as $needle => $district) {
            if (mb_strpos($text, $needle) !== false) {
                return $district;
            }
        }

        return null;
    }

    public static function resolveFromCoords(mixed $latitude, mixed $longitude): ?string
    {
        if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
            return null;
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;
        if ($lat < 24.2 || $lat > 27.2 || $lng < 113.5 || $lng > 117.2) {
            return null;
        }

        $best = null;
        $bestKm = PHP_FLOAT_MAX;

        foreach (self::CENTERS as $name => $center) {
            $km = self::haversineKm($lat, $lng, $center['lat'], $center['lng']);
            if ($km < $bestKm) {
                $bestKm = $km;
                $best = $name;
            }
        }

        if ($best === null || $bestKm > self::MAX_CENTER_KM) {
            return null;
        }

        return $best;
    }

    private static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }
}
