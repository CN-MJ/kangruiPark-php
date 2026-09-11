<?php

namespace App\Services;

use App\Models\ParkingEvent;
use App\Models\ParkingRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ParkingService
{
    public const SOURCE_MANUAL = 0;

    public const SOURCE_HARDWARE = 1;

    public function getTotalSpots(): int
    {
        return (int) config('parking.total_spots', 100);
    }

    /**
     * @return array{total_spots:int,occupied_count:int,remaining_spots:int}
     */
    public function getRemainingSpots(): array
    {
        $totalSpots = $this->getTotalSpots();
        $occupiedCount = ParkingRecord::where('status', ParkingRecord::STATUS_IN_PARK)->count();
        $remaining = max(0, $totalSpots - $occupiedCount);

        return [
            'total_spots' => $totalSpots,
            'occupied_count' => $occupiedCount,
            'remaining_spots' => $remaining,
        ];
    }

    public function getActiveRecord(string $plateNumber): ?ParkingRecord
    {
        $plateKey = ParkingRecord::normalizePlate($plateNumber);

        if ($plateKey === '') {
            return null;
        }

        return ParkingRecord::query()
            ->inParkForPlate($plateKey)
            ->orderByDesc('entry_time')
            ->first();
    }

    /**
     * @param  array{
     *     plate_number:string,
     *     entry_time?:\DateTimeInterface|string|null,
     *     source_type?:int,
     *     event_id?:int|null,
     *     entry_device_id?:int|null
     * }  $options
     *
     * @throws ParkingServiceException
     */
    public function entry(array $options): ParkingRecord
    {
        $plateKey = ParkingRecord::normalizePlate($options['plate_number'] ?? '');

        if ($plateKey === '') {
            throw ParkingServiceException::badRequest('请输入有效的车牌号');
        }

        $sourceType = (int) ($options['source_type'] ?? self::SOURCE_MANUAL);
        $eventId = $options['event_id'] ?? null;
        $entryDeviceId = $options['entry_device_id'] ?? null;
        $entryTime = $options['entry_time'] ?? now();

        try {
            $record = DB::transaction(function () use ($plateKey, $sourceType, $eventId, $entryDeviceId, $entryTime) {
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

                if ($occupiedCount >= $this->getTotalSpots()) {
                    throw ParkingServiceException::badRequest('车位已满，无法预约');
                }

                return ParkingRecord::create([
                    'plate_number' => $plateKey,
                    'plate_key' => $plateKey,
                    'active_plate_key' => $plateKey,
                    'entry_time' => $entryTime,
                    'status' => ParkingRecord::STATUS_IN_PARK,
                    'source_type' => $sourceType,
                    'event_id' => $eventId,
                    'entry_device_id' => $entryDeviceId,
                ]);
            });
        } catch (QueryException $e) {
            if ($this->isDuplicatePlateError($e)) {
                throw ParkingServiceException::badRequest('该车辆已在场内，请勿重复入场');
            }
            throw $e;
        }

        if ($record === null) {
            throw ParkingServiceException::badRequest('该车辆已在场内，请勿重复入场');
        }

        return $record;
    }

    /**
     * @param  array{
     *     exit_time?:\DateTimeInterface|string|null,
     *     source_type?:int,
     *     event_id?:int|null,
     *     exit_device_id?:int|null
     * }  $options
     *
     * @throws ParkingServiceException
     */
    public function exitByPlate(string $plateNumber, array $options = []): ParkingRecord
    {
        $plateKey = ParkingRecord::normalizePlate($plateNumber);

        if ($plateKey === '') {
            throw ParkingServiceException::badRequest('请输入有效的车牌号');
        }

        $record = ParkingRecord::query()
            ->inParkForPlate($plateKey)
            ->orderByDesc('entry_time')
            ->first();

        if (! $record) {
            throw ParkingServiceException::notFound('未找到该车辆的在场记录');
        }

        return $this->markRecordExited($record, $options);
    }

    /**
     * @param  array{
     *     exit_time?:\DateTimeInterface|string|null,
     *     source_type?:int,
     *     event_id?:int|null,
     *     exit_device_id?:int|null
     * }  $options
     *
     * @throws ParkingServiceException
     */
    public function exitById(int $id, array $options = []): ParkingRecord
    {
        $record = ParkingRecord::find($id);

        if (! $record) {
            throw ParkingServiceException::notFound('停车记录不存在');
        }

        if ($record->status === ParkingRecord::STATUS_LEFT) {
            throw ParkingServiceException::badRequest('该车辆已离场');
        }

        return $this->markRecordExited($record, $options);
    }

    /**
     * @param  array{
     *     exit_time?:\DateTimeInterface|string|null,
     *     source_type?:int,
     *     event_id?:int|null,
     *     exit_device_id?:int|null
     * }  $options
     */
    private function markRecordExited(ParkingRecord $record, array $options = []): ParkingRecord
    {
        $exitTime = $options['exit_time'] ?? null;
        if (is_string($exitTime)) {
            $exitTime = new \DateTime($exitTime);
        }

        $updates = [];
        if (isset($options['source_type'])) {
            $updates['source_type'] = (int) $options['source_type'];
        }
        if (! empty($options['event_id'])) {
            $updates['event_id'] = $options['event_id'];
        }
        if (! empty($options['exit_device_id'])) {
            $updates['exit_device_id'] = $options['exit_device_id'];
        }
        if ($updates !== []) {
            $record->update($updates);
        }

        $record->markExited($exitTime);

        return $record->fresh();
    }

    private function isDuplicatePlateError(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'parking_records_active_plate_key_unique')
            || str_contains($message, 'Duplicate entry');
    }
}
