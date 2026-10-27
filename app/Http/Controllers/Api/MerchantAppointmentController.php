<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Services\AppointmentWechatNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantAppointmentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AppointmentWechatNotifier $wechatNotifier,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $merchant = $request->attributes->get('merchant_user');

        $query = Appointment::query()
            ->with([
                'enterprise:id,name',
                'enterpriseService:id,name',
            ])
            ->orderByDesc('created_at');

        // 优先按关联企业收预约；无企业绑定时回退 merchant_id
        if ($merchant->enterprise_id) {
            $query->where(function ($q) use ($merchant) {
                $q->where('enterprise_id', $merchant->enterprise_id)
                    ->orWhere('merchant_id', $merchant->id);
            });
        } else {
            $query->where('merchant_id', $merchant->id);
        }

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        $list = $query->paginate(20);

        $items = collect($list->items())->map(function (Appointment $item) {
            $item->status_text = $item->statusText();

            return [
                'id' => $item->id,
                'user_name' => $item->user_name,
                'user_phone' => $item->user_phone,
                'appointment_time' => optional($item->appointment_time)?->toDateTimeString(),
                'resource_name' => $item->resource_name,
                'status' => (int) $item->status,
                'status_text' => $item->status_text,
                'enterprise_id' => $item->enterprise_id,
                'enterprise' => $item->enterprise ? [
                    'id' => $item->enterprise->id,
                    'name' => $item->enterprise->name,
                ] : null,
                'enterprise_service_id' => $item->enterprise_service_id,
                'enterprise_service' => $item->enterpriseService ? [
                    'id' => $item->enterpriseService->id,
                    'name' => $item->enterpriseService->name,
                ] : null,
                'created_at' => optional($item->created_at)?->toDateTimeString(),
            ];
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
        $appointment = Appointment::query()
            ->with(['enterpriseService:id,name'])
            ->find($id);
        if (! $appointment) {
            return $this->error('预约记录不存在', 404);
        }
        if (! $this->ownsAppointment($merchant, $appointment)) {
            return $this->error('无权操作该预约', 403);
        }
        if ($appointment->status !== Appointment::STATUS_PENDING) {
            return $this->error('仅待确认预约可操作', 400);
        }

        $appointment->update(['status' => $targetStatus]);
        $appointment->status_text = $appointment->statusText();

        $this->wechatNotifier->notifyVisitorAppointmentResult($appointment);

        return $this->success($appointment, $message);
    }

    private function ownsAppointment($merchant, Appointment $appointment): bool
    {
        if ((int) $appointment->merchant_id === (int) $merchant->id) {
            return true;
        }

        return $merchant->enterprise_id
            && (int) $appointment->enterprise_id === (int) $merchant->enterprise_id;
    }
}
