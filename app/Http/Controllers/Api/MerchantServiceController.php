<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\UploadsServiceImage;
use App\Http\Traits\ApiResponse;
use App\Models\EnterpriseService;
use App\Models\Merchant;
use App\Services\ImageCompressService;
use App\Support\EnterpriseServicePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 企业工作台：管理本企业发布的服务（商品）。
 */
class MerchantServiceController extends Controller
{
    use ApiResponse;
    use UploadsServiceImage;

    public function __construct(
        private readonly ImageCompressService $imageCompress,
    ) {}

    public function uploadImage(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);
        $enterpriseId = $this->requireEnterpriseId($merchant);
        if ($enterpriseId instanceof JsonResponse) {
            return $enterpriseId;
        }

        return $this->storeServiceImage($request, $this->imageCompress);
    }

    public function index(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);
        $enterpriseId = $this->requireEnterpriseId($merchant);
        if ($enterpriseId instanceof JsonResponse) {
            return $enterpriseId;
        }

        $list = EnterpriseService::query()
            ->where('enterprise_id', $enterpriseId)
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (EnterpriseService $s) => $this->format($s));

        return $this->success(['list' => $list]);
    }

    public function store(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);
        $enterpriseId = $this->requireEnterpriseId($merchant);
        if ($enterpriseId instanceof JsonResponse) {
            return $enterpriseId;
        }

        $validated = EnterpriseServicePayload::normalize($this->validatePayload($request));
        $validated['enterprise_id'] = $enterpriseId;
        $validated['status'] = $validated['status'] ?? EnterpriseService::STATUS_ON;
        if (! array_key_exists('price_text', $validated)) {
            $validated['price_text'] = EnterpriseServicePayload::DEFAULT_PRICE;
        }

        $service = EnterpriseService::create($validated);

        return $this->success($this->format($service), '服务已发布');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $merchant = $this->merchant($request);
        $enterpriseId = $this->requireEnterpriseId($merchant);
        if ($enterpriseId instanceof JsonResponse) {
            return $enterpriseId;
        }

        $service = EnterpriseService::query()
            ->where('enterprise_id', $enterpriseId)
            ->find($id);

        if (! $service) {
            return $this->error('服务不存在', 404);
        }

        $service->update(EnterpriseServicePayload::normalize($this->validatePayload($request, false)));

        return $this->success($this->format($service->fresh()), '已保存');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $merchant = $this->merchant($request);
        $enterpriseId = $this->requireEnterpriseId($merchant);
        if ($enterpriseId instanceof JsonResponse) {
            return $enterpriseId;
        }

        $service = EnterpriseService::query()
            ->where('enterprise_id', $enterpriseId)
            ->find($id);

        if (! $service) {
            return $this->error('服务不存在', 404);
        }

        $service->delete();

        return $this->success(null, '已删除');
    }

    /** 快捷上架/下架 */
    public function setStatus(Request $request, int $id): JsonResponse
    {
        $merchant = $this->merchant($request);
        $enterpriseId = $this->requireEnterpriseId($merchant);
        if ($enterpriseId instanceof JsonResponse) {
            return $enterpriseId;
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([EnterpriseService::STATUS_ON, EnterpriseService::STATUS_OFF])],
        ]);

        $service = EnterpriseService::query()
            ->where('enterprise_id', $enterpriseId)
            ->find($id);

        if (! $service) {
            return $this->error('服务不存在', 404);
        }

        $service->update(['status' => (int) $validated['status']]);

        return $this->success(
            $this->format($service->fresh()),
            (int) $validated['status'] === EnterpriseService::STATUS_ON ? '已上架' : '已下架'
        );
    }

    private function merchant(Request $request): Merchant
    {
        return $request->attributes->get('merchant_user');
    }

    private function requireEnterpriseId(Merchant $merchant): int|JsonResponse
    {
        if (! $merchant->enterprise_id) {
            return $this->error('账号未关联入驻企业，请联系园区管理员绑定后使用', 400);
        }

        return (int) $merchant->enterprise_id;
    }

    private function validatePayload(Request $request, bool $creating = true): array
    {
        $rules = [
            'name' => ($creating ? 'required' : 'sometimes').'|string|max:120',
            'summary' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'price_text' => 'nullable|string|max:80',
            'images' => 'nullable|array|max:'.EnterpriseServicePayload::MAX_IMAGES,
            'images.*' => 'string|max:500',
            'status' => ['sometimes', Rule::in([EnterpriseService::STATUS_ON, EnterpriseService::STATUS_OFF])],
            'sort_order' => 'sometimes|integer|min:0|max:99999',
        ];

        return $request->validate($rules, [
            'name.required' => '请填写服务名称',
            'images.max' => '最多上传 '.EnterpriseServicePayload::MAX_IMAGES.' 张图片',
        ]);
    }

    private function format(EnterpriseService $s): array
    {
        return EnterpriseServicePayload::format($s);
    }
}
