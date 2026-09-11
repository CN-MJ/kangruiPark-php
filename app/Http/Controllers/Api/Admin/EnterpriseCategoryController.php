<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Enterprise;
use App\Models\EnterpriseCategory;
use App\Support\EnsureEnterpriseSchema;
use Database\Seeders\EnterpriseCategorySeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class EnterpriseCategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('enterprise_categories')) {
            return $this->error('企业分类表未初始化，请点击「同步标准 6 大类」或执行 php artisan migrate', 503);
        }

        $tree = $request->boolean('tree', true);

        if ($tree) {
            $majors = EnterpriseCategory::query()
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
                ->get();

            return $this->success([
                'list' => $majors->map(fn (EnterpriseCategory $m) => $this->formatNode($m, true))->values(),
            ]);
        }

        $query = EnterpriseCategory::query()->orderBy('sort_order')->orderBy('id');
        if ($request->filled('parent_id')) {
            if ($request->input('parent_id') === '0' || $request->input('parent_id') === 0) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', (int) $request->input('parent_id'));
            }
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        return $this->success([
            'list' => $query->get()->map(fn (EnterpriseCategory $c) => $this->formatNode($c))->values(),
        ]);
    }

    /**
     * 写入标准 6 大类 + 二级小类（可重复执行，已存在则更新）
     */
    public function syncDefaults(): JsonResponse
    {
        try {
            EnsureEnterpriseSchema::run();
            (new EnterpriseCategorySeeder)->run();
        } catch (\Throwable $e) {
            report($e);

            return $this->error('同步失败：'.$e->getMessage(), 500);
        }

        $majors = EnterpriseCategory::query()
            ->whereNull('parent_id')
            ->where('status', EnterpriseCategory::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->get();

        return $this->success([
            'list' => $majors->map(fn (EnterpriseCategory $m) => $this->formatNode($m, true))->values(),
            'major_count' => $majors->count(),
            'minor_count' => $majors->sum(fn (EnterpriseCategory $m) => $m->children->count()),
        ], '标准分类已同步');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);
        $category = EnterpriseCategory::create($data);

        return $this->success($this->formatNode($category->fresh()), '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = EnterpriseCategory::find($id);
        if (! $category) {
            return $this->error('类型不存在', 404);
        }

        $category->update($this->validateData($request, $category));

        return $this->success($this->formatNode($category->fresh()), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $category = EnterpriseCategory::find($id);
        if (! $category) {
            return $this->error('类型不存在', 404);
        }

        if ($category->isMajor() && $category->children()->exists()) {
            return $this->error('请先删除或迁移该大类下的小类', 400);
        }

        $asPrimary = Enterprise::query()->where('primary_category_id', $id)->exists();
        $asTag = \Illuminate\Support\Facades\DB::table('enterprise_category')
            ->where('enterprise_category_id', $id)
            ->exists();

        if ($asPrimary || $asTag) {
            return $this->error('该类型已被企业使用，请先调整企业分类或改为停用', 400);
        }

        $category->delete();

        return $this->success(null, '删除成功');
    }

    private function validateData(Request $request, ?EnterpriseCategory $current = null): array
    {
        $rawParent = $request->input('parent_id', $current?->parent_id);
        if ($rawParent === '' || $rawParent === '0' || $rawParent === 0 || $rawParent === null) {
            $parentId = null;
        } else {
            $parentId = (int) $rawParent;
        }

        $data = $request->validate([
      'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('enterprise_categories', 'name')
                    ->where(fn ($q) => $parentId === null
                        ? $q->whereNull('parent_id')
                        : $q->where('parent_id', $parentId))
                    ->ignore($current?->id),
            ],
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if ($parentId !== null) {
            $parentOk = EnterpriseCategory::query()
                ->whereKey($parentId)
                ->whereNull('parent_id')
                ->exists();
            if (! $parentOk) {
                abort(response()->json([
                    'code' => 422,
                    'msg' => '父级必须是一级大类',
                    'data' => null,
                ]));
            }
        }

        if ($current && $current->isMajor() && $current->children()->exists() && $parentId !== null) {
            abort(response()->json([
                'code' => 400,
                'msg' => '已有小类的大类不能改为小类',
                'data' => null,
            ]));
        }

        $data['parent_id'] = $parentId;
        $data['sort_order'] = (int) ($data['sort_order'] ?? $current?->sort_order ?? 0);
        $data['status'] = (int) ($data['status'] ?? $current?->status ?? EnterpriseCategory::STATUS_ACTIVE);

        return $data;
    }

    private function formatNode(EnterpriseCategory $c, bool $withChildren = false): array
    {
        $row = [
            'id' => $c->id,
            'parent_id' => $c->parent_id,
            'name' => $c->name,
            'sort_order' => $c->sort_order,
            'status' => (int) $c->status,
            'is_major' => $c->isMajor(),
        ];

        if ($withChildren) {
            $row['children'] = $c->children->map(fn (EnterpriseCategory $child) => $this->formatNode($child))->values();
        }

        return $row;
    }
}
