<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Enterprise;
use App\Models\EnterpriseService;
use App\Support\EnterpriseServicePayload;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 公开：按关键词搜索上架服务（含所属企业摘要）。
 */
class EnterpriseServiceController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 10), 1), 50);

        $query = EnterpriseService::query()
            ->onShelf()
            ->with([
                'enterprise' => fn ($q) => $q
                    ->where('status', Enterprise::STATUS_ON)
                    ->with(['primaryCategory:id,name', 'categories:id,name']),
            ])
            ->whereHas('enterprise', fn ($q) => $q->where('status', Enterprise::STATUS_ON))
            ->orderByDesc('sort_order')
            ->orderByDesc('id');

        if ($request->filled('enterprise_id')) {
            $query->where('enterprise_id', (int) $request->input('enterprise_id'));
        }

        if ($request->filled('major_id')) {
            $majorId = (int) $request->input('major_id');
            $query->whereHas('enterprise', fn ($q) => $q->where('primary_category_id', $majorId));
        }

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->input('category_id');
            $query->whereHas(
                'enterprise.categories',
                fn ($q) => $q->where('enterprise_categories.id', $categoryId)
            );
        }

        if ($request->filled('settle_in_park')) {
            $settle = filter_var($request->input('settle_in_park'), FILTER_VALIDATE_BOOLEAN);
            $query->whereHas('enterprise', fn ($q) => $q->where('settle_in_park', $settle));
        }

        if ($request->filled('service_audience')) {
            $audience = trim((string) $request->input('service_audience'));
            if (in_array($audience, ['b', 'c', 'bc'], true)) {
                $query->whereHas('enterprise', fn ($q) => $q->where('service_audience', $audience));
            }
        }

        if ($request->filled('district')) {
            $district = trim((string) $request->input('district'));
            if ($district !== '') {
                $query->whereHas('enterprise', fn ($q) => $q->where('district', $district));
            }
        }

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->input('keyword'));
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('summary', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
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

    public function show(int $id): JsonResponse
    {
        $service = EnterpriseService::query()
            ->onShelf()
            ->with([
                'enterprise' => fn ($q) => $q
                    ->where('status', Enterprise::STATUS_ON)
                    ->with(['primaryCategory:id,name', 'categories:id,name']),
            ])
            ->find($id);

        if (! $service || ! $service->enterprise) {
            return $this->error('服务不存在或已下架', 404);
        }

        return $this->success($this->format($service, true));
    }

    private function format(EnterpriseService $s, bool $detail = false): array
    {
        $e = $s->enterprise;
        $cover = null;
        if ($e) {
            $images = MediaUrl::absoluteList(
                is_array($e->images) ? array_values($e->images) : [],
                request(),
            );
            $cover = $images[0] ?? null;
        }

        $serviceImages = MediaUrl::absoluteList(
            is_array($s->images) ? array_values($s->images) : [],
            request(),
        );

        $data = [
            'id' => $s->id,
            'name' => $s->name,
            'summary' => $s->summary,
            'price_text' => $s->price_text ?: EnterpriseServicePayload::DEFAULT_PRICE,
            'cover' => $serviceImages[0] ?? $cover,
            'images' => $serviceImages,
            'enterprise_id' => $s->enterprise_id,
            'enterprise' => $e ? [
                'id' => $e->id,
                'name' => $e->name,
                'cover' => $cover,
                'district' => $e->district,
                'settle_in_park' => (bool) $e->settle_in_park,
                'primary_category' => $e->primaryCategory ? [
                    'id' => $e->primaryCategory->id,
                    'name' => $e->primaryCategory->name,
                ] : null,
            ] : null,
        ];

        if ($detail) {
            $data['description'] = $s->description;
        }

        return $data;
    }
}
