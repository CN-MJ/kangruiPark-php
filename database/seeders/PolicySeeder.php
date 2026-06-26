<?php

namespace Database\Seeders;

use App\Models\Policy;
use Illuminate\Database\Seeder;

class PolicySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['title' => '高新企业税收减免政策', 'level' => '市级', 'content' => '<p>符合条件企业可享受税收减免。</p>', 'sort_order' => 10, 'is_show' => true],
            ['title' => '园区人才补贴政策', 'level' => '园区级', 'content' => '<p>对引进高层次人才给予专项补贴。</p>', 'sort_order' => 9, 'is_show' => true],
        ];
        foreach ($data as $item) {
            Policy::query()->updateOrCreate(['title' => $item['title']], $item);
        }
    }
}
