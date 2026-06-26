<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => '系统管理员',
                'password' => Hash::make('admin123'),
            ]
        );
    }
}
