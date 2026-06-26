<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use ApiResponse;

    /**
     * 获取预约列表（支持按手机号筛选）
     */
    public function index(Request $request): JsonResponse
    {
        $query = Appointment::query()
            ->with('merchant:id,name,location')
            ->orderByDesc('created_at');

        if ($request->filled('user_phone')) {
            $query->where('user_phone', $request->input('user_phone'));
        }

        $appointments = $query->get();
        $appointments->each(function (Appointment $item): void {
            $item->status_text = $item->statusText();
        });

        return $this->success($appointments);
    }

    /**
     * 提交预约
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_name' => 'required|string|max:50',
            'user_phone' => 'required|string|max:20',
            'appointment_time' => 'required|date',
            'resource_name' => 'required_without:merchant_id|string|max:100',
            'merchant_id' => 'required_without:resource_name|integer|exists:merchants,id',
        ], [
            'user_name.required' => '请输入姓名',
            'user_phone.required' => '请输入手机号',
            'appointment_time.required' => '请选择预约时间',
            'merchant_id.required_without' => '请选择拜访公司',
            'resource_name.required_without' => '请选择预约项目',
            'merchant_id.exists' => '所选公司不存在',
        ]);

        $data = [
            'user_name' => $validated['user_name'],
            'user_phone' => $validated['user_phone'],
            'appointment_time' => $validated['appointment_time'],
            'status' => Appointment::STATUS_PENDING,
        ];

        if (! empty($validated['merchant_id'])) {
            $merchant = Merchant::query()
                ->active()
                ->find($validated['merchant_id']);

            if (! $merchant) {
                return $this->error('所选公司暂不可预约', 400);
            }

            $data['merchant_id'] = $merchant->id;
            $data['resource_name'] = $merchant->name;
        } else {
            $data['resource_name'] = $validated['resource_name'];
        }

        $appointment = Appointment::create($data);
        $appointment->load('merchant:id,name,location');

        return $this->success($appointment, '预约提交成功');
    }

    /**
     * 取消预约
     */
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
        $appointment->status_text = $appointment->statusText();

        return $this->success($appointment, '预约已取消');
    }
}
