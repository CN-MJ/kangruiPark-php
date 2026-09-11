<?php

namespace Database\Seeders;

use App\Models\Merchant;
use Illuminate\Database\Seeder;

class MerchantSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = [
            [
                'name' => '赣南健康科技有限公司',
                'contact_name' => '张经理',
                'contact_phone' => '13800001001',
                'location' => 'A栋 3层',
                'sort_order' => 1,
            ],
            [
                'name' => '星辰文化传媒',
                'contact_name' => '李总',
                'contact_phone' => '13800001002',
                'location' => 'A栋 5层',
                'sort_order' => 2,
            ],
            [
                'name' => '绿野新能源',
                'contact_name' => '王工',
                'contact_phone' => '13800001003',
                'location' => 'B栋 2层',
                'sort_order' => 3,
            ],
            [
                'name' => '蓝海创意设计',
                'contact_name' => '陈总监',
                'contact_phone' => '13800001004',
                'location' => 'B栋 6层',
                'sort_order' => 4,
            ],
            [
                'name' => '智慧物联研发中心',
                'contact_name' => '刘主任',
                'contact_phone' => '13800001005',
                'location' => 'C栋 1层',
                'sort_order' => 5,
            ],
            [
                'name' => '云帆电子商务',
                'contact_name' => '赵经理',
                'contact_phone' => '13800001006',
                'location' => 'C栋 4层',
                'sort_order' => 6,
            ],
        ];

        foreach ($merchants as $merchant) {
            Merchant::query()->updateOrCreate(
                ['name' => $merchant['name']],
                [
                    ...$merchant,
                    'status' => Merchant::STATUS_ACTIVE,
                    'password' => '123456',
                ]
            );
        }
    }
}
