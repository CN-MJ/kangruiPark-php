<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantAppointmentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $merchant = $request->attributes->get('merchant_user');
        $list = Appointment::query()
            ->where('merchant_id', $merchant->id)
            ->orderByDesc('created_at')
            ->paginate(20);

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

    public function confirm(Request $request, int $id): JsonResponse
    {
        return $this->updateStatus($request, $id, Appointment::STATUS_CONFIRMED, '预约已确认');
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        return $this->updateStatus($request, $id, Appointment::STATUS_REJECTED, '预约已拒绝');
    }

    private function updateStatus(Request $request, int $id, int $targetStatus, string $message): JsonResponse
    {
        $merchant = $request->attributes->get('merchant_user');
        $appointment = Appointment::query()->find($id);
        if (! $appointment) {
            return $this->error('预约记录不存在', 404);
        }
        if ((int) $appointment->merchant_id !== (int) $merchant->id) {
            return $this->error('无权操作该预约', 403);
        }
        if ($appointment->status !== Appointment::STATUS_PENDING) {
            return $this->error('仅待确认预约可操作', 400);
        }

        $appointment->update(['status' => $targetStatus]);
        $appointment->status_text = $appointment->statusText();

        return $this->success($appointment, $message);
    }
}
