<?php

namespace Database\Seeders;

use App\Models\BuildingHouse;
use Illuminate\Database\Seeder;

class BuildingHouseSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['title' => 'A1栋 1201', 'area' => '360㎡', 'price' => '3980元/㎡', 'house_type' => '独立产权', 'description' => '临主路，适合研发总部。', 'images' => [], 'status' => 1, 'sort_order' => 10],
            ['title' => 'B3栋 501', 'area' => '220㎡', 'price' => '3200元/㎡', 'house_type' => '灵活分割', 'description' => '近园区食堂，适合成长型团队。', 'images' => [], 'status' => 1, 'sort_order' => 9],
        ];
        foreach ($data as $item) {
            BuildingHouse::query()->updateOrCreate(['title' => $item['title']], $item);
        }
    }
}
