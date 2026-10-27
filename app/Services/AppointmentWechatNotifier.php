<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\EnterpriseService;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * 预约业务的服务号模板消息：失败只记日志，不影响主流程。
 */
class AppointmentWechatNotifier
{
    public function __construct(
        private readonly WeChatService $wechat,
    ) {}

    /**
     * 访客提交预约 → 通知企业负责人（merchants.mp_openid）。
     */
    public function notifyMerchantNewAppointment(Appointment $appointment): void
    {
        try {
            $openids = $this->resolveMerchantOpenids($appointment);
            if ($openids->isEmpty()) {
                return;
            }

            $templateId = (string) config('services.wechat_mp.templates.to_merchant', 'TEST_TPL_TO_MERCHANT');
            $timeText = optional($appointment->appointment_time)?->format('Y-m-d H:i') ?: '-';
            $serviceName = (string) (
                $appointment->enterpriseService?->name
                ?: $appointment->resource_name
                ?: '预约拜访'
            );

            // 模板字段名需与公众平台一致；未配置真实 template_id 前仅为占位
            $data = [
                'thing1' => ['value' => mb_substr((string) $appointment->user_name, 0, 20)],
                'phone_number2' => ['value' => (string) $appointment->user_phone],
                'time3' => ['value' => $timeText],
                'thing4' => ['value' => mb_substr($serviceName, 0, 20)],
                'thing5' => ['value' => '待确认'],
            ];

            foreach ($openids as $openid) {
                $this->wechat->sendTemplateMessage($openid, $templateId, $data);
            }
        } catch (\Throwable $e) {
            Log::warning('appointment.notify_merchant_failed', [
                'appointment_id' => $appointment->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 企业或园区确认/拒绝 → 通知访客（xcx_users.mp_openid，按预约手机号）。
     */
    public function notifyVisitorAppointmentResult(Appointment $appointment): void
    {
        try {
            $phone = trim((string) $appointment->user_phone);
            if ($phone === '') {
                Log::info('appointment.notify_visitor.skip', [
                    'appointment_id' => $appointment->id,
                    'reason' => '预约无手机号',
                ]);

                return;
            }

            $user = User::query()->where('phone', $phone)->first();
            $openid = is_string($user?->mp_openid) ? trim($user->mp_openid) : '';

            if ($openid === '') {
                Log::info('appointment.notify_visitor.skip', [
                    'appointment_id' => $appointment->id,
                    'phone' => $phone,
                    'reason' => '访客未绑定',
                ]);

                return;
            }

            $templateId = (string) config('services.wechat_mp.templates.to_visitor', 'TEST_TPL_TO_VISITOR');
            $timeText = optional($appointment->appointment_time)?->format('Y-m-d H:i') ?: '-';
            $statusText = $appointment->statusText();
            $serviceName = (string) (
                $appointment->enterpriseService?->name
                ?: $appointment->resource_name
                ?: '预约'
            );

            $data = [
                'thing1' => ['value' => mb_substr($serviceName, 0, 20)],
                'time2' => ['value' => $timeText],
                'phrase3' => ['value' => mb_substr($statusText, 0, 5)],
                'thing4' => ['value' => mb_substr('请打开小程序查看详情', 0, 20)],
            ];

            $this->wechat->sendTemplateMessage($openid, $templateId, $data);
        } catch (\Throwable $e) {
            Log::warning('appointment.notify_visitor_failed', [
                'appointment_id' => $appointment->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 服务预约：enterprise_service_id → enterprise_id → merchants.mp_openid
     *
     * @return Collection<int, string>
     */
    private function resolveMerchantOpenids(Appointment $appointment): Collection
    {
        $enterpriseId = (int) ($appointment->enterprise_id ?: 0);

        if ($enterpriseId <= 0 && $appointment->enterprise_service_id) {
            $service = $appointment->relationLoaded('enterpriseService') && $appointment->enterpriseService
                ? $appointment->enterpriseService
                : EnterpriseService::query()->find($appointment->enterprise_service_id);
            $enterpriseId = (int) ($service?->enterprise_id ?: 0);
        }

        if ($enterpriseId <= 0) {
            Log::info('appointment.notify_merchant.skip', [
                'appointment_id' => $appointment->id,
                'enterprise_service_id' => $appointment->enterprise_service_id,
                'reason' => '无法解析 enterprise_id',
            ]);

            return collect();
        }

        $openids = Merchant::query()
            ->active()
            ->where('enterprise_id', $enterpriseId)
            ->whereNotNull('mp_openid')
            ->where('mp_openid', '!=', '')
            ->pluck('mp_openid')
            ->map(fn ($oid) => trim((string) $oid))
            ->filter()
            ->unique()
            ->values();

        if ($openids->isEmpty()) {
            Log::info('appointment.notify_merchant.skip', [
                'appointment_id' => $appointment->id,
                'enterprise_id' => $enterpriseId,
                'enterprise_service_id' => $appointment->enterprise_service_id,
                'reason' => '企业未绑定',
            ]);
        }

        return $openids;
    }
}
