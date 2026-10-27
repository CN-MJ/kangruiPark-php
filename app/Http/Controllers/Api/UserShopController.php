<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\UploadsServiceImage;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\EnterpriseService;
use App\Models\User;
use App\Services\AppointmentWechatNotifier;
use App\Services\ImageCompressService;
use App\Services\MerchantEnterpriseBinder;
use App\Support\EnterpriseServicePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 微信端商户：基于已绑定企业，管理服务与处理预约（无需密码工作台）。
 */
class UserShopController extends Controller
{
    use ApiResponse;
    use UploadsServiceImage;

    public function __construct(
        private readonly MerchantEnterpriseBinder $binder,
        private readonly AppointmentWechatNotifier $wechatNotifier,
        private readonly ImageCompressService $imageCompress,
    ) {}

    public function uploadImage(Request $request): JsonResponse
    {
        $user = $this->merchantUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        return $this->storeServiceImage($request, $this->imageCompress);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->merchantUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $user->load('enterprise:id,name,status,settle_in_park');
        $merchant = $this->binder->resolveMerchant($user);

        return $this->success([
            'enterprise_id' => $user->enterprise_id,
            'enterprise' => $user->enterprise ? [
                'id' => $user->enterprise->id,
                'name' => $user->enterprise->name,
                'status' => $user->enterprise->status,
                'settle_in_park' => (bool) $user->enterprise->settle_in_park,
            ] : null,
            'merchant_account_id' => $merchant?->id,
            'has_mp_openid' => is_string($user->mp_openid) && $user->mp_openid !== '',
        ]);
    }

    public function services(Request $request): JsonResponse
    {
        $user = $this->merchantUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $list = EnterpriseService::query()
            ->where('enterprise_id', $user->enterprise_id)
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (EnterpriseService $s) => $this->formatService($s));

        return $this->success(['list' => $list]);
    }

    public function storeService(Request $request): JsonResponse
    {
        $user = $this->merchantUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $validated = EnterpriseServicePayload::normalize($this->validateService($request));
        $validated['enterprise_id'] = $user->enterprise_id;
        $validated['status'] = $validated['status'] ?? EnterpriseService::STATUS_ON;
        if (! array_key_exists('price_text', $validated)) {
            $validated['price_text'] = EnterpriseServicePayload::DEFAULT_PRICE;
        }

        $service = EnterpriseService::create($validated);

        return $this->success($this->formatService($service), '服务已发布');
    }

    public function updateService(Request $request, int $id): JsonResponse
    {
        $user = $this->merchantUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $service = EnterpriseService::query()
            ->where('enterprise_id', $user->enterprise_id)
            ->find($id);
        if (! $service) {
            return $this->error('服务不存在', 404);
        }

        $service->update(EnterpriseServicePayload::normalize($this->validateService($request, false)));

        return $this->success($this->formatService($service->fresh()), '已保存');
    }

    public function setServiceStatus(Request $request, int $id): JsonResponse
    {
        $user = $this->merchantUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([EnterpriseService::STATUS_ON, EnterpriseService::STATUS_OFF])],
        ]);

        $service = EnterpriseService::query()
            ->where('enterprise_id', $user->enterprise_id)
            ->find($id);
        if (! $service) {
            return $this->error('服务不存在', 404);
        }

        $service->update(['status' => (int) $validated['status']]);

        return $this->success(
            $this->formatService($service->fresh()),
            (int) $validated['status'] === EnterpriseService::STATUS_ON ? '已上架' : '已下架'
        );
    }

    public function destroyService(Request $request, int $id): JsonResponse
    {
        $user = $this->merchantUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $service = EnterpriseService::query()
            ->where('enterprise_id', $user->enterprise_id)
            ->find($id);
        if (! $service) {
            return $this->error('服务不存在', 404);
        }

        $service->delete();

        return $this->success(null, '已删除');
    }

    public function appointments(Request $request): JsonResponse
    {
        $user = $this->merchantUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $merchant = $this->binder->resolveMerchant($user);

        $query = Appointment::query()
            ->with(['enterprise:id,name', 'enterpriseService:id,name'])
            ->where(function ($q) use ($user, $merchant) {
                $q->where('enterprise_id', $user->enterprise_id);
                if ($merchant) {
                    $q->orWhere('merchant_id', $merchant->id);
                }
            })
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        $list = $query->paginate(20);
        $items = collect($list->items())->map(function (Appointment $item) {
            return [
                'id' => $item->id,
                'user_name' => $item->user_name,
                'user_phone' => $item->user_phone,
                'appointment_time' => optional($item->appointment_time)?->toDateTimeString(),
                'resource_name' => $item->resource_name,
                'status' => (int) $item->status,
                'status_text' => $item->statusText(),
                'enterprise' => $item->enterprise ? [
                    'id' => $item->enterprise->id,
                    'name' => $item->enterprise->name,
                ] : null,
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

    public function showAppointment(Request $request, int $id): JsonResponse
    {
        $user = $this->merchantUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $merchant = $this->binder->resolveMerchant($user);
        $appointment = Appointment::query()
            ->with(['enterprise:id,name', 'enterpriseService:id,name,summary,price_text'])
            ->find($id);
        if (! $appointment) {
            return $this->error('预约记录不存在', 404);
        }

        $owned = (int) $appointment->enterprise_id === (int) $user->enterprise_id
            || ($merchant && (int) $appointment->merchant_id === (int) $merchant->id);
        if (! $owned) {
            return $this->error('无权查看该预约', 403);
        }

        return $this->success([
            'id' => $appointment->id,
            'user_name' => $appointment->user_name,
            'user_phone' => $appointment->user_phone,
            'appointment_time' => optional($appointment->appointment_time)?->toDateTimeString(),
            'resource_name' => $appointment->resource_name,
            'status' => (int) $appointment->status,
            'status_text' => $appointment->statusText(),
            'enterprise' => $appointment->enterprise ? [
                'id' => $appointment->enterprise->id,
                'name' => $appointment->enterprise->name,
            ] : null,
            'enterprise_service' => $appointment->enterpriseService ? [
                'id' => $appointment->enterpriseService->id,
                'name' => $appointment->enterpriseService->name,
                'summary' => $appointment->enterpriseService->summary,
                'price_text' => $appointment->enterpriseService->price_text,
            ] : null,
            'created_at' => optional($appointment->created_at)?->toDateTimeString(),
            'updated_at' => optional($appointment->updated_at)?->toDateTimeString(),
        ]);
    }

    public function confirmAppointment(Request $request, int $id): JsonResponse
    {
        return $this->updateAppointmentStatus($request, $id, Appointment::STATUS_CONFIRMED, '预约已确认');
    }

    public function rejectAppointment(Request $request, int $id): JsonResponse
    {
        return $this->updateAppointmentStatus($request, $id, Appointment::STATUS_REJECTED, '预约已拒绝');
    }

    private function updateAppointmentStatus(Request $request, int $id, int $status, string $message): JsonResponse
    {
        $user = $this->merchantUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $merchant = $this->binder->resolveMerchant($user);
        $appointment = Appointment::query()
            ->with(['enterpriseService:id,name'])
            ->find($id);
        if (! $appointment) {
            return $this->error('预约记录不存在', 404);
        }

        $owned = (int) $appointment->enterprise_id === (int) $user->enterprise_id
            || ($merchant && (int) $appointment->merchant_id === (int) $merchant->id);
        if (! $owned) {
            return $this->error('无权操作该预约', 403);
        }
        if ($appointment->status !== Appointment::STATUS_PENDING) {
            return $this->error('仅待确认预约可操作', 400);
        }

        $appointment->update(['status' => $status]);
        $appointment->status_text = $appointment->statusText();
        $this->wechatNotifier->notifyVisitorAppointmentResult($appointment);

        return $this->success($appointment, $message);
    }

    private function merchantUser(Request $request): User|JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('xcx_user');
        if (! $user->isMerchant()) {
            return $this->error('请先在个人资料中选择商户身份并绑定企业', 403);
        }
        if (! $user->enterprise_id) {
            return $this->error('尚未绑定企业，请先在资料中选择所属企业', 400);
        }

        return $user;
    }

    private function validateService(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'name' => ($creating ? 'required' : 'sometimes').'|string|max:120',
            'summary' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'price_text' => 'nullable|string|max:80',
            'images' => 'nullable|array|max:'.EnterpriseServicePayload::MAX_IMAGES,
            'images.*' => 'string|max:500',
            'status' => ['sometimes', Rule::in([EnterpriseService::STATUS_ON, EnterpriseService::STATUS_OFF])],
            'sort_order' => 'sometimes|integer|min:0|max:99999',
        ], [
            'name.required' => '请填写服务名称',
            'images.max' => '最多上传 '.EnterpriseServicePayload::MAX_IMAGES.' 张图片',
        ]);
    }

    private function formatService(EnterpriseService $s): array
    {
        return EnterpriseServicePayload::format($s);
    }
}
