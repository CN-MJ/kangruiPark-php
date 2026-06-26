<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['phone' => '13800000001'],
            [
                'name' => '园区工作人员',
                'role' => User::ROLE_PARK,
            ]
        );
    }
}
