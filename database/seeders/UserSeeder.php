<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\ParkPermissions;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['phone' => '13800000001'],
            [
                'name' => '园区管理员',
                'role' => User::ROLE_PARK,
                'park_role' => ParkPermissions::ROLE_ADMIN,
                'password' => '123456',
            ]
        );

        User::query()->updateOrCreate(
            ['phone' => '13800000002'],
            [
                'name' => '健康企业汇录入员',
                'role' => User::ROLE_PARK,
                'park_role' => ParkPermissions::ROLE_ENTERPRISE,
                'password' => '12345678',
            ]
        );

        User::query()->updateOrCreate(
            ['phone' => '13800000003'],
            [
                'name' => '分类管理员',
                'role' => User::ROLE_PARK,
                'park_role' => ParkPermissions::ROLE_CATEGORY,
                'password' => '12345678',
            ]
        );

        // 外观与普通访客无异，仅 can_escalate=true 可升权进控制台
        User::query()->updateOrCreate(
            ['phone' => '13900000001'],
            [
                'name' => '可升超管访客',
                'role' => User::ROLE_VISITOR,
                'password' => '123456',
                'phone_verified_at' => now(),
                'can_escalate' => true,
            ]
        );

        User::query()->updateOrCreate(
            ['phone' => '13900000002'],
            [
                'name' => '普通访客',
                'role' => User::ROLE_VISITOR,
                'phone_verified_at' => now(),
                'can_escalate' => false,
            ]
        );
    }
}
