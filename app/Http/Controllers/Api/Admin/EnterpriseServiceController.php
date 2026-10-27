<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Enterprise;
use App\Models\EnterpriseService;
use App\Support\EnterpriseServicePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 园区后台：代企业维护服务，或查看全部服务。
 */
class EnterpriseServiceController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 20), 1), 50);

        $query = EnterpriseService::query()
            ->with('enterprise:id,name,status')
            ->orderByDesc('id');

        if ($request->filled('enterprise_id')) {
            $query->where('enterprise_id', (int) $request->input('enterprise_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }
        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->input('keyword'));
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhereHas('enterprise', fn ($eq) => $eq->where('name', 'like', "%{$keyword}%"));
                });
            }
        }

        $page = $query->paginate($perPage);

        return $this->success([
            'list' => collect($page->items())->map(fn (EnterpriseService $s) => $this->format($s))->values(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request, true);
        if (! Enterprise::query()->whereKey($validated['enterprise_id'])->exists()) {
            return $this->error('企业不存在', 404);
        }

        $validated = EnterpriseServicePayload::normalize($validated);
        if (! array_key_exists('price_text', $validated)) {
            $validated['price_text'] = EnterpriseServicePayload::DEFAULT_PRICE;
        }
        $service = EnterpriseService::create($validated);

        return $this->success($this->format($service->load('enterprise:id,name,status')), '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $service = EnterpriseService::query()->find($id);
        if (! $service) {
            return $this->error('服务不存在', 404);
        }

        $service->update(EnterpriseServicePayload::normalize($this->validatePayload($request, false)));

        return $this->success($this->format($service->fresh()->load('enterprise:id,name,status')), '已保存');
    }

    public function destroy(int $id): JsonResponse
    {
        $service = EnterpriseService::query()->find($id);
        if (! $service) {
            return $this->error('服务不存在', 404);
        }

        $service->delete();

        return $this->success(null, '已删除');
    }

    private function validatePayload(Request $request, bool $creating): array
    {
        return $request->validate([
            'enterprise_id' => ($creating ? 'required' : 'sometimes').'|integer|exists:enterprises,id',
            'name' => ($creating ? 'required' : 'sometimes').'|string|max:120',
            'summary' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'price_text' => 'nullable|string|max:80',
            'images' => 'nullable|array|max:'.EnterpriseServicePayload::MAX_IMAGES,
            'images.*' => 'string|max:500',
            'status' => ['sometimes', Rule::in([EnterpriseService::STATUS_ON, EnterpriseService::STATUS_OFF])],
            'sort_order' => 'sometimes|integer|min:0|max:99999',
        ], [
            'enterprise_id.required' => '请选择企业',
            'name.required' => '请填写服务名称',
            'images.max' => '最多上传 '.EnterpriseServicePayload::MAX_IMAGES.' 张图片',
        ]);
    }

    private function format(EnterpriseService $s): array
    {
        $data = EnterpriseServicePayload::format($s);
        $data['enterprise'] = $s->enterprise ? [
            'id' => $s->enterprise->id,
            'name' => $s->enterprise->name,
        ] : null;

        return $data;
    }
}
