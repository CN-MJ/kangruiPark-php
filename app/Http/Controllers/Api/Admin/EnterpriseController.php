<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Enterprise;
use App\Models\EnterpriseCategory;
use App\Models\User;
use App\Support\GanzhouDistrict;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EnterpriseController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 20), 1), 50);

        $query = Enterprise::query()
            ->with([
                'primaryCategory:id,name,parent_id',
                'categories:id,name,parent_id',
            ])
            ->orderByDesc('id');

        if ($request->filled('major_id')) {
            $query->where('primary_category_id', (int) $request->input('major_id'));
        }
        if ($request->filled('category_id')) {
            $categoryId = (int) $request->input('category_id');
            $query->whereHas('categories', fn ($q) => $q->where('enterprise_categories.id', $categoryId));
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
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
        if ($request->filled('district')) {
            $district = trim((string) $request->input('district'));
            if (GanzhouDistrict::isValid($district)) {
                $query->where('district', $district);
            }
        }
        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->input('keyword'));
            if ($keyword !== '' && strcasecmp($keyword, 'undefined') !== 0 && strcasecmp($keyword, 'null') !== 0) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('business_intro', 'like', "%{$keyword}%")
                        ->orWhere('contact_name', 'like', "%{$keyword}%")
                        ->orWhere('contact_phone', 'like', "%{$keyword}%")
                        ->orWhere('address', 'like', "%{$keyword}%")
                        ->orWhere('district', 'like', "%{$keyword}%");
                });
            }
        }

        $page = $query->paginate($perPage);

        return $this->success([
            'list' => collect($page->items())->map(fn (Enterprise $e) => $this->format($e))->values(),
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
        $payload = $this->validateData($request);
        $categoryIds = $payload['category_ids'];
        unset($payload['category_ids']);

        /** @var User|null $parkUser */
        $parkUser = $request->attributes->get('park_user');
        $payload['created_by'] = $parkUser?->id;

        $enterprise = Enterprise::create($payload);
        $enterprise->categories()->sync($categoryIds);
        $enterprise->load(['primaryCategory:id,name,parent_id', 'categories:id,name,parent_id']);

        return $this->success($this->format($enterprise), '创建成功');
    }

    public function show(int $id): JsonResponse
    {
        $enterprise = Enterprise::query()
            ->with(['primaryCategory:id,name,parent_id', 'categories:id,name,parent_id'])
            ->find($id);

        if (! $enterprise) {
            return $this->error('企业不存在', 404);
        }

        return $this->success($this->format($enterprise));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $enterprise = Enterprise::find($id);
        if (! $enterprise) {
            return $this->error('企业不存在', 404);
        }

        $payload = $this->validateData($request);
        $categoryIds = $payload['category_ids'];
        unset($payload['category_ids']);

        $previousImages = is_array($enterprise->images) ? $enterprise->images : [];
        $enterprise->update($payload);
        $this->deleteUnusedEnterpriseImages($enterprise->id, $previousImages, $payload['images'] ?? []);
        $enterprise->categories()->sync($categoryIds);
        $enterprise->load(['primaryCategory:id,name,parent_id', 'categories:id,name,parent_id']);

        return $this->success($this->format($enterprise->fresh()), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $enterprise = Enterprise::find($id);
        if (! $enterprise) {
            return $this->error('企业不存在', 404);
        }
        // 假删除：保留分类关联，恢复后仍可直接使用
        $enterprise->delete();

        return $this->success(null, '已删除（可恢复）');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'contact_name' => 'nullable|string|max:50',
            'contact_phone' => 'nullable|string|max:20',
            'primary_category_id' => [
                'required',
                'integer',
                Rule::exists('enterprise_categories', 'id')->whereNull('parent_id'),
            ],
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => [
                'integer',
                Rule::exists('enterprise_categories', 'id')->whereNotNull('parent_id'),
            ],
            'business_intro' => 'required|string|max:2000',
            'address' => 'nullable|string|max:255',
            'district' => ['nullable', 'string', Rule::in(GanzhouDistrict::NAMES)],
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'map_enabled' => 'nullable|boolean',
            'service_audience' => ['required', Rule::in([Enterprise::AUDIENCE_C, Enterprise::AUDIENCE_B, Enterprise::AUDIENCE_BC])],
            'source' => ['required', Rule::in([Enterprise::SOURCE_COMMUNITY, Enterprise::SOURCE_SELF])],
            'settle_in_park' => 'nullable|boolean',
            'images' => 'nullable|array|max:5',
            'images.*' => 'string|max:500',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $primaryId = (int) $data['primary_category_id'];
        $categoryIds = array_values(array_unique(array_map('intval', $data['category_ids'])));

        $invalid = EnterpriseCategory::query()
            ->whereIn('id', $categoryIds)
            ->where(function ($q) use ($primaryId) {
                $q->whereNull('parent_id')->orWhere('parent_id', '!=', $primaryId);
            })
            ->exists();

        if ($invalid) {
            abort(response()->json([
                'code' => 422,
                'msg' => '小类必须属于所选主大类',
                'data' => null,
            ]));
        }

        $data['category_ids'] = $categoryIds;
        $data['map_enabled'] = (bool) ($data['map_enabled'] ?? false);
        if (! $data['map_enabled']) {
            $data['latitude'] = null;
            $data['longitude'] = null;
        }
        $data['settle_in_park'] = (bool) ($data['settle_in_park'] ?? false);
        $data['images'] = array_values(array_filter(array_map(
            static fn ($img) => MediaUrl::toRelative(is_string($img) ? $img : null) ?: (is_string($img) ? $img : null),
            array_slice($data['images'] ?? [], 0, 5),
        )));
        $data['status'] = (int) ($data['status'] ?? Enterprise::STATUS_ON);

        $manualDistrict = isset($data['district']) ? trim((string) $data['district']) : '';
        if ($manualDistrict !== '' && GanzhouDistrict::isValid($manualDistrict)) {
            $data['district'] = $manualDistrict;
        } else {
            $data['district'] = GanzhouDistrict::resolve(
                $data['address'] ?? null,
                $data['latitude'] ?? null,
                $data['longitude'] ?? null,
            );
        }

        return $data;
    }

    /**
     * 保存后删掉这次从列表里移除、且没有其它企业仍在使用的企业图片。
     *
     * @param  array<int, mixed>  $previous
     * @param  array<int, mixed>  $current
     */
    private function deleteUnusedEnterpriseImages(int $enterpriseId, array $previous, array $current): void
    {
        $before = $this->enterpriseImagePaths($previous);
        $after = $this->enterpriseImagePaths($current);
        $rawCurrent = array_values(array_filter($current, static fn ($img) => is_string($img) && trim($img) !== ''));
        // 客户端若传来无法解析的路径，禁止误删磁盘上的原图
        if ($rawCurrent !== [] && $after === []) {
            return;
        }
        $removed = array_values(array_diff($before, $after));

        foreach ($removed as $relative) {
            $stillUsed = Enterprise::query()
                ->where('id', '!=', $enterpriseId)
                ->whereJsonContains('images', $relative)
                ->exists();
            if ($stillUsed) {
                continue;
            }

            $diskFile = Storage::disk('public')->path($relative);
            $publicFile = public_path('storage/'.$relative);
            $diskReal = is_file($diskFile) ? realpath($diskFile) : false;
            $publicReal = is_file($publicFile) ? realpath($publicFile) : false;

            Storage::disk('public')->delete($relative);
            if ($publicReal && $publicReal !== $diskReal && is_file($publicFile)) {
                @unlink($publicFile);
            }
        }
    }

    /**
     * @param  array<int, mixed>  $images
     * @return list<string>
     */
    private function enterpriseImagePaths(array $images): array
    {
        $paths = [];
        foreach ($images as $image) {
            if (! is_string($image)) {
                continue;
            }
            $relative = MediaUrl::toRelative($image);
            if ($relative !== null && str_starts_with($relative, 'uploads/enterprises/')) {
                $paths[] = $relative;
            }
        }

        return array_values(array_unique($paths));
    }

    private function format(Enterprise $e, ?Request $request = null): array
    {
        $request = $request ?: request();
        $images = MediaUrl::absoluteList(
            is_array($e->images) ? array_values($e->images) : [],
            $request,
        );

        return [
            'id' => $e->id,
            'name' => $e->name,
            'contact_name' => $e->contact_name,
            'contact_phone' => $e->contact_phone,
            'primary_category_id' => $e->primary_category_id,
            'category_ids' => $e->categories->pluck('id')->values(),
            'business_intro' => $e->business_intro,
            'address' => $e->address,
            'district' => $e->district,
            'latitude' => $e->latitude,
            'longitude' => $e->longitude,
            'map_enabled' => (bool) $e->map_enabled,
            'service_audience' => $e->service_audience,
            'service_audience_text' => Enterprise::audienceText($e->service_audience),
            'source' => $e->source,
            'source_text' => Enterprise::sourceText($e->source),
            'settle_in_park' => (bool) $e->settle_in_park,
            'images' => $images,
            'cover' => $images[0] ?? null,
            'status' => (int) $e->status,
            'created_by' => $e->created_by,
            'primary_category' => $e->primaryCategory ? [
                'id' => $e->primaryCategory->id,
                'name' => $e->primaryCategory->name,
            ] : null,
            'categories' => $e->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values(),
            'created_at' => optional($e->created_at)?->toDateTimeString(),
            'updated_at' => optional($e->updated_at)?->toDateTimeString(),
        ];
    }
}
