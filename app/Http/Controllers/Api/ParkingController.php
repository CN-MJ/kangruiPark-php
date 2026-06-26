<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ParkingRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ParkingController extends Controller
{
    use ApiResponse;

    /**
     * 查询剩余车位
     */
    public function remaining(): JsonResponse
    {
        $totalSpots = (int) config('app.total_parking_spots', 100);
        $occupiedCount = ParkingRecord::where('status', ParkingRecord::STATUS_IN_PARK)->count();
        $remaining = max(0, $totalSpots - $occupiedCount);

        return $this->success([
            'total_spots' => $totalSpots,
            'occupied_count' => $occupiedCount,
            'remaining_spots' => $remaining,
        ]);
    }

    /**
     * 提交停车预约（入场）
     */
    public function entry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plate_number' => 'required|string|max:20',
            'entry_time' => 'nullable|date',
        ], [
            'plate_number.required' => '请输入车牌号',
            'plate_number.max' => '车牌号过长',
        ]);

        $plateKey = ParkingRecord::normalizePlate($validated['plate_number']);

        if ($plateKey === '') {
            return $this->error('请输入有效的车牌号', 400);
        }

        try {
            $record = DB::transaction(function () use ($plateKey, $validated) {
                $existing = ParkingRecord::query()
                    ->inParkForPlate($plateKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return null;
                }

                $occupiedCount = ParkingRecord::where('status', ParkingRecord::STATUS_IN_PARK)
                    ->lockForUpdate()
                    ->count();

                $totalSpots = (int) config('app.total_parking_spots', 100);
                if ($occupiedCount >= $totalSpots) {
                    throw new \RuntimeException('FULL');
                }

                return ParkingRecord::create([
                    'plate_number' => $plateKey,
                    'plate_key' => $plateKey,
                    'active_plate_key' => $plateKey,
                    'entry_time' => $validated['entry_time'] ?? now(),
                    'status' => ParkingRecord::STATUS_IN_PARK,
                ]);
            });
        } catch (QueryException $e) {
            if ($this->isDuplicatePlateError($e)) {
                return $this->error('该车辆已在场内，请勿重复入场', 400);
            }
            throw $e;
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'FULL') {
                return $this->error('车位已满，无法预约', 400);
            }
            throw $e;
        }

        if ($record === null) {
            return $this->error('该车辆已在场内，请勿重复入场', 400);
        }

        return $this->success($record, '停车预约成功');
    }

    /**
     * 查询车辆在场记录（按车牌）
     */
    public function active(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plate_number' => 'required|string|max:20',
        ], [
            'plate_number.required' => '请输入车牌号',
        ]);

        $plateKey = ParkingRecord::normalizePlate($validated['plate_number']);

        $record = ParkingRecord::query()
            ->inParkForPlate($plateKey)
            ->orderByDesc('entry_time')
            ->first();

        if (! $record) {
            return $this->error('该车辆暂无在场记录', 404);
        }

        return $this->success($record);
    }

    /**
     * 车辆出场（按车牌）
     */
    public function exitByPlate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plate_number' => 'required|string|max:20',
        ], [
            'plate_number.required' => '请输入车牌号',
        ]);

        $plateKey = ParkingRecord::normalizePlate($validated['plate_number']);

        $record = ParkingRecord::query()
            ->inParkForPlate($plateKey)
            ->orderByDesc('entry_time')
            ->first();

        if (! $record) {
            return $this->error('未找到该车辆的在场记录', 404);
        }

        $record->markExited();

        return $this->success($record->fresh(), '出场成功，一路平安');
    }

    /**
     * 出场（按记录 ID）
     */
    public function exit(Request $request, int $id): JsonResponse
    {
        $record = ParkingRecord::find($id);

        if (! $record) {
            return $this->error('停车记录不存在', 404);
        }

        if ($record->status === ParkingRecord::STATUS_LEFT) {
            return $this->error('该车辆已离场', 400);
        }

        $validated = $request->validate([
            'exit_time' => 'nullable|date',
        ]);

        $record->markExited(isset($validated['exit_time']) ? new \DateTime($validated['exit_time']) : null);

        return $this->success($record->fresh(), '出场成功');
    }

    private function isDuplicatePlateError(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'parking_records_active_plate_key_unique')
            || str_contains($message, 'Duplicate entry');
    }
}
