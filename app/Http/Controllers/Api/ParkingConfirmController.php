<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ParkingEvent;
use App\Models\ParkingRecord;
use App\Models\User;
use App\Models\UserVehicle;
use App\Services\ParkingService;
use App\Services\ParkingServiceException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParkingConfirmController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ParkingService $parkingService,
    ) {}

    public function pendingEvents(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('xcx_user');

        $plates = UserVehicle::query()
            ->where('user_id', $user->id)
            ->where('status', UserVehicle::STATUS_ACTIVE)
            ->pluck('plate_number')
            ->all();

        if ($plates === []) {
            return $this->success(['list' => [], 'count' => 0]);
        }

        $events = ParkingEvent::query()
            ->with('device:id,device_name,location,direction')
            ->whereIn('plate_number', $plates)
            ->where('processed_status', ParkingEvent::STATUS_PENDING)
            ->orderByDesc('recognition_time')
            ->get();

        $activePlates = ParkingRecord::query()
            ->whereIn('plate_key', $plates)
            ->where('status', ParkingRecord::STATUS_IN_PARK)
            ->pluck('entry_time', 'plate_key');

        $list = $events->map(function (ParkingEvent $event) use ($activePlates) {
            $entryTime = $activePlates[$event->plate_number] ?? null;

            return [
                'id' => $event->id,
                'plate_number' => $event->plate_number,
                'direction' => $event->direction,
                'direction_text' => $event->direction === ParkingEvent::DIRECTION_ENTRY ? '入场' : '出场',
                'recognition_time' => $event->recognition_time,
                'image_url' => $event->image_url,
                'confidence' => $event->confidence,
                'device' => $event->device,
                'entry_time' => $entryTime,
            ];
        })->values();

        return $this->success([
            'list' => $list,
            'count' => $list->count(),
        ]);
    }

    public function confirmEntry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|integer|exists:parking_events,id',
        ]);

        /** @var User $user */
        $user = $request->attributes->get('xcx_user');

        try {
            $record = DB::transaction(function () use ($user, $validated) {
                $event = ParkingEvent::query()->lockForUpdate()->find($validated['event_id']);

                if (! $event || ! $event->isPending()) {
                    throw ParkingServiceException::badRequest('事件不存在或已处理');
                }

                if ($event->direction !== ParkingEvent::DIRECTION_ENTRY) {
                    throw ParkingServiceException::badRequest('该事件不是入场事件');
                }

                $this->assertUserOwnsPlate($user, $event->plate_number);

                $record = $this->parkingService->entry([
                    'plate_number' => $event->plate_number,
                    'entry_time' => $event->recognition_time,
                    'source_type' => ParkingService::SOURCE_HARDWARE,
                    'event_id' => $event->id,
                    'entry_device_id' => $event->device_id,
                ]);

                $event->markProcessed();

                return $record;
            });
        } catch (ParkingServiceException $e) {
            return $this->error($e->getMessage(), $e->statusCode);
        }

        return $this->success($record, '入场确认成功');
    }

    public function confirmExit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|integer|exists:parking_events,id',
        ]);

        /** @var User $user */
        $user = $request->attributes->get('xcx_user');

        try {
            $record = DB::transaction(function () use ($user, $validated) {
                $event = ParkingEvent::query()->lockForUpdate()->find($validated['event_id']);

                if (! $event || ! $event->isPending()) {
                    throw ParkingServiceException::badRequest('事件不存在或已处理');
                }

                if ($event->direction !== ParkingEvent::DIRECTION_EXIT) {
                    throw ParkingServiceException::badRequest('该事件不是出场事件');
                }

                $this->assertUserOwnsPlate($user, $event->plate_number);

                $record = $this->parkingService->exitByPlate($event->plate_number, [
                    'exit_time' => $event->recognition_time,
                    'source_type' => ParkingService::SOURCE_HARDWARE,
                    'event_id' => $event->id,
                    'exit_device_id' => $event->device_id,
                ]);

                $event->markProcessed();

                return $record;
            });
        } catch (ParkingServiceException $e) {
            return $this->error($e->getMessage(), $e->statusCode);
        }

        return $this->success($record, '出场确认成功');
    }

    private function assertUserOwnsPlate(User $user, string $plateNumber): void
    {
        $plateKey = ParkingRecord::normalizePlate($plateNumber);

        $owns = UserVehicle::query()
            ->where('user_id', $user->id)
            ->where('plate_number', $plateKey)
            ->where('status', UserVehicle::STATUS_ACTIVE)
            ->exists();

        if (! $owns) {
            throw ParkingServiceException::badRequest('您未绑定该车牌，无法确认');
        }
    }
}
