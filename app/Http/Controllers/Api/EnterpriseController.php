<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Enterprise;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnterpriseController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 10), 1), 50);

        $query = Enterprise::query()
            ->with([
                'primaryCategory:id,name,parent_id',
                'categories:id,name,parent_id',
            ])
            ->where('status', Enterprise::STATUS_ON)
            ->orderByDesc('id');

        if ($request->filled('major_id')) {
            $query->where('primary_category_id', (int) $request->input('major_id'));
        }

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->input('category_id');
            $query->whereHas('categories', fn ($q) => $q->where('enterprise_categories.id', $categoryId));
        }

        if ($request->filled('service_audience')) {
            $query->where('service_audience', $request->input('service_audience'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        if ($request->filled('settle_in_park')) {
            $query->where('settle_in_park', filter_var($request->input('settle_in_park'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->input('keyword'));
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('business_intro', 'like', "%{$keyword}%")
                    ->orWhere('address', 'like', "%{$keyword}%")
                    ->orWhere('contact_name', 'like', "%{$keyword}%");
            });
        }

        $page = $query->paginate($perPage);

        return $this->success([
            'list' => collect($page->items())->map(fn (Enterprise $e) => $this->formatPublic($e))->values(),
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
        $enterprise = Enterprise::query()
            ->with([
                'primaryCategory:id,name,parent_id',
                'categories:id,name,parent_id',
            ])
            ->where('status', Enterprise::STATUS_ON)
            ->find($id);

        if (! $enterprise) {
            return $this->error('企业不存在或已下架', 404);
        }

        return $this->success($this->formatPublic($enterprise, true));
    }

    private function formatPublic(Enterprise $e, bool $detail = false): array
    {
        $images = MediaUrl::absoluteList(
            is_array($e->images) ? array_values($e->images) : [],
            request(),
        );

        $data = [
            'id' => $e->id,
            'name' => $e->name,
            'business_intro' => $e->business_intro,
            'address' => $e->address,
            'map_enabled' => (bool) $e->map_enabled,
            'latitude' => $e->map_enabled ? $e->latitude : null,
            'longitude' => $e->map_enabled ? $e->longitude : null,
            'service_audience' => $e->service_audience,
            'service_audience_text' => Enterprise::audienceText($e->service_audience),
            'source' => $e->source,
            'source_text' => Enterprise::sourceText($e->source),
            'settle_in_park' => (bool) $e->settle_in_park,
            'cover' => $images[0] ?? null,
            'images' => $detail ? $images : array_slice($images, 0, 1),
            'primary_category' => $e->primaryCategory ? [
                'id' => $e->primaryCategory->id,
                'name' => $e->primaryCategory->name,
            ] : null,
            'categories' => $e->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values(),
        ];

        if ($detail) {
            $data['contact_name'] = $e->contact_name;
            $data['contact_phone'] = $e->contact_phone;
            $data['created_at'] = optional($e->created_at)?->toDateTimeString();
        }

        return $data;
    }
}
