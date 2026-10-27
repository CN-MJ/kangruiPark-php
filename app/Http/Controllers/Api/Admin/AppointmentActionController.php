<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Services\AppointmentWechatNotifier;
use Illuminate\Http\JsonResponse;

class AppointmentActionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AppointmentWechatNotifier $wechatNotifier,
    ) {}

    public function confirm(int $id): JsonResponse
    {
        return $this->updateStatus($id, Appointment::STATUS_CONFIRMED, '预约已确认');
    }

    public function reject(int $id): JsonResponse
    {
        return $this->updateStatus($id, Appointment::STATUS_REJECTED, '预约已拒绝');
    }

    private function updateStatus(int $id, int $status, string $message): JsonResponse
    {
        $appointment = Appointment::query()
            ->with(['enterpriseService:id,name'])
            ->find($id);
        if (! $appointment) {
            return $this->error('预约记录不存在', 404);
        }
        if ($appointment->status !== Appointment::STATUS_PENDING) {
            return $this->error('仅待确认预约可操作', 400);
        }

        $appointment->update(['status' => $status]);
        $appointment->status_text = $appointment->statusText();

        // 园区代操作同样通知访客
        $this->wechatNotifier->notifyVisitorAppointmentResult($appointment);

        return $this->success($appointment, $message);
    }
}
