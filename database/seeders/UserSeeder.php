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
                'password' => '123456',
            ]
        );

        // 外观与普通访客无异，仅 can_escalate=true 可升权进控制台
        User::query()->updateOrCreate(
            ['phone' => '13900000001'],
            [
                'name' => '园区访客',
                'role' => User::ROLE_VISITOR,
                'password' => '123456',
                'phone_verified_at' => now(),
                'can_escalate' => true,
            ]
        );
    }
}
