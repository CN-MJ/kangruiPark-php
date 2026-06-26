<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ParkingRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParkingController extends Controller
{
    use ApiResponse;

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

        $record->markExited(
            isset($validated['exit_time']) ? new \DateTime($validated['exit_time']) : null
        );

        return $this->success($record->fresh(), '出场成功');
    }
}
