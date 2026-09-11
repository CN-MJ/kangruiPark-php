<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ParkingEvent;
use App\Models\ParkingRecord;
use App\Models\ParkingDevice;
use App\Models\UserVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HardwareRecognitionController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        /** @var ParkingDevice $device */
        $device = $request->attributes->get('parking_device');

        $validated = $request->validate([
            'event_id' => 'required|string|max:64',
            'plate_number' => 'required|string|max:20',
            'recognition_time' => 'required|date',
            'image_url' => 'nullable|string|max:500',
            'confidence' => 'nullable|numeric|min:0|max:1',
            'direction' => 'required|integer|in:1,2',
        ], [
            'event_id.required' => '缺少设备事件ID',
            'plate_number.required' => '缺少车牌号',
            'recognition_time.required' => '缺少识别时间',
            'direction.required' => '缺少通行方向',
        ]);

        $direction = (int) $validated['direction'];

        if ($direction !== (int) $device->direction) {
            return $this->error('设备方向与事件方向不匹配', 400);
        }

        $existing = ParkingEvent::query()
            ->where('event_id', $validated['event_id'])
            ->first();

        if ($existing) {
            return $this->success([
                'event' => $existing,
                'duplicate' => true,
            ], '事件已接收');
        }

        $plateKey = ParkingRecord::normalizePlate($validated['plate_number']);

        if ($plateKey === '') {
            return $this->error('车牌号无效', 400);
        }

        $event = DB::transaction(function () use ($device, $validated, $direction, $plateKey) {
            return ParkingEvent::create([
                'device_id' => $device->id,
                'plate_number' => $plateKey,
                'direction' => $direction,
                'recognition_time' => $validated['recognition_time'],
                'image_url' => $validated['image_url'] ?? null,
                'confidence' => $validated['confidence'] ?? null,
                'event_id' => $validated['event_id'],
                'processed_status' => ParkingEvent::STATUS_PENDING,
            ]);
        });

        $matchedUsers = UserVehicle::query()
            ->where('plate_number', $plateKey)
            ->where('status', UserVehicle::STATUS_ACTIVE)
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();

        return $this->success([
            'event' => $event,
            'matched_user_ids' => $matchedUsers,
            'duplicate' => false,
        ], '识别事件已记录');
    }
}
