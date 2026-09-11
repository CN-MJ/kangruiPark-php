<?php

namespace Database\Seeders;

use App\Models\ParkingDevice;
use Illuminate\Database\Seeder;

class ParkingDeviceSeeder extends Seeder
{
    public function run(): void
    {
        ParkingDevice::query()->updateOrCreate(
            ['device_name' => '东门入口摄像头'],
            [
                'device_type' => ParkingDevice::TYPE_ENTRY_CAMERA,
                'direction' => ParkingDevice::DIRECTION_ENTRY,
                'location' => '园区东门',
                'api_key' => 'demo-entry-api-key-001',
                'status' => ParkingDevice::STATUS_ACTIVE,
            ]
        );

        ParkingDevice::query()->updateOrCreate(
            ['device_name' => '东门出口摄像头'],
            [
                'device_type' => ParkingDevice::TYPE_EXIT_CAMERA,
                'direction' => ParkingDevice::DIRECTION_EXIT,
                'location' => '园区东门',
                'api_key' => 'demo-exit-api-key-001',
                'status' => ParkingDevice::STATUS_ACTIVE,
            ]
        );
    }
}
