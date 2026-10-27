<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Appointment::query()
            ->with([
                'merchant:id,name,location',
                'enterprise:id,name',
                'enterpriseService:id,name',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('user_phone')) {
            $query->where('user_phone', 'like', '%'.$request->input('user_phone').'%');
        }

        if ($request->filled('enterprise_id')) {
            $query->where('enterprise_id', (int) $request->input('enterprise_id'));
        }

        $list = $query->paginate(20);
        $items = collect($list->items())->map(function (Appointment $item) {
            $item->status_text = $item->statusText();
            return $item;
        })->all();

        return $this->success([
            'list' => $items,
            'pagination' => [
                'current_page' => $list->currentPage(),
                'per_page' => $list->perPage(),
                'total' => $list->total(),
                'last_page' => $list->lastPage(),
            ],
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (! $appointment) {
            return $this->error('预约记录不存在', 404);
        }

        if ($appointment->status !== Appointment::STATUS_PENDING) {
            return $this->error('仅待确认预约可取消', 400);
        }

        $appointment->update(['status' => Appointment::STATUS_CANCELLED]);

        return $this->success($appointment, '预约已取消');
    }
}
