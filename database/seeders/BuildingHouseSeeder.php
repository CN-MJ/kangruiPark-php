<?php

namespace Database\Seeders;

use App\Models\BuildingHouse;
use Illuminate\Database\Seeder;

class BuildingHouseSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/building_houses.json');
        if (! is_file($path)) {
            $this->command?->error('缺少数据文件：database/seeders/data/building_houses.json');

            return;
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows) || $rows === []) {
            $this->command?->error('房源 JSON 为空或格式错误');

            return;
        }

        $count = 0;
        foreach ($rows as $item) {
            if (! is_array($item)) {
                continue;
            }

            $payload = BuildingHouse::syncDerivedFields(array_merge([
                'price' => '',
                'description' => '',
                'images' => [],
                'lease_sale_status' => BuildingHouse::LEASE_AVAILABLE,
                'status' => BuildingHouse::STATUS_ON_MARKET,
                'sort_order' => 0,
                'house_type' => '',
            ], $item));

            BuildingHouse::query()->updateOrCreate(
                [
                    'building_no' => $payload['building_no'],
                    'room_no' => $payload['room_no'],
                ],
                $payload
            );
            $count++;
        }

        $this->command?->info("已导入/更新 {$count} 套房源");
    }
}
