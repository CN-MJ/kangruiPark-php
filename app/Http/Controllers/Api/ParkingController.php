<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\ParkingService;
use App\Services\ParkingServiceException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParkingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ParkingService $parkingService,
    ) {}

    public function remaining(): JsonResponse
    {
        return $this->success($this->parkingService->getRemainingSpots());
    }

    public function entry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plate_number' => 'required|string|max:20',
            'entry_time' => 'nullable|date',
        ], [
            'plate_number.required' => '请输入车牌号',
            'plate_number.max' => '车牌号过长',
        ]);

        try {
            $record = $this->parkingService->entry([
                'plate_number' => $validated['plate_number'],
                'entry_time' => $validated['entry_time'] ?? null,
                'source_type' => ParkingService::SOURCE_MANUAL,
            ]);
        } catch (ParkingServiceException $e) {
            return $this->error($e->getMessage(), $e->statusCode);
        }

        return $this->success($record, '停车预约成功');
    }

    public function active(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plate_number' => 'required|string|max:20',
        ], [
            'plate_number.required' => '请输入车牌号',
        ]);

        $record = $this->parkingService->getActiveRecord($validated['plate_number']);

        if (! $record) {
            return $this->error('该车辆暂无在场记录', 404);
        }

        return $this->success($record);
    }

    public function exitByPlate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plate_number' => 'required|string|max:20',
        ], [
            'plate_number.required' => '请输入车牌号',
        ]);

        try {
            $record = $this->parkingService->exitByPlate($validated['plate_number']);
        } catch (ParkingServiceException $e) {
            return $this->error($e->getMessage(), $e->statusCode);
        }

        return $this->success($record, '出场成功，一路平安');
    }

    public function exit(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'exit_time' => 'nullable|date',
        ]);

        try {
            $record = $this->parkingService->exitById($id, [
                'exit_time' => $validated['exit_time'] ?? null,
            ]);
        } catch (ParkingServiceException $e) {
            return $this->error($e->getMessage(), $e->statusCode);
        }

        return $this->success($record, '出场成功');
    }
}
