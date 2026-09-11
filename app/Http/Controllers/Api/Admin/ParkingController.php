<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ParkingRecord;
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

    public function index(Request $request): JsonResponse
    {
        $query = ParkingRecord::query()->orderByDesc('entry_time');

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('plate_number')) {
            $query->where('plate_number', 'like', '%'.$request->input('plate_number').'%');
        }

        $list = $query->paginate(20);

        return $this->success([
            'list' => $list->items(),
            'pagination' => [
                'current_page' => $list->currentPage(),
                'per_page' => $list->perPage(),
                'total' => $list->total(),
                'last_page' => $list->lastPage(),
            ],
        ]);
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
