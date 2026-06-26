<?php

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        Activity::query()->updateOrCreate(
            ['title' => '产业对接沙龙'],
            [
                'location' => 'A栋路演厅',
                'description' => '园区企业产业链对接交流活动。',
                'start_time' => now()->addDays(3),
                'end_time' => now()->addDays(3)->addHours(2),
                'max_attendees' => 80,
                'registered_count' => 0,
                'status' => Activity::STATUS_OPEN,
            ]
        );
    }
}
