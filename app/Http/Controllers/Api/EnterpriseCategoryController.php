<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\EnterpriseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnterpriseCategoryController extends Controller
{
    use ApiResponse;

    /**
     * 公开：启用中的大类及其小类树
     */
    public function index(Request $request): JsonResponse
    {
        $majors = EnterpriseCategory::query()
            ->whereNull('parent_id')
            ->where('status', EnterpriseCategory::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with(['children' => function ($q) {
                $q->where('status', EnterpriseCategory::STATUS_ACTIVE)
                    ->orderBy('sort_order')
                    ->orderBy('id');
            }])
            ->get();

        if ($request->input('flat') == 1) {
            return $this->success([
                'list' => $majors->flatMap(function (EnterpriseCategory $major) {
                    return $major->children->map(fn (EnterpriseCategory $c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'parent_id' => $major->id,
                        'parent_name' => $major->name,
                        'sort_order' => $c->sort_order,
                    ]);
                })->values(),
            ]);
        }

        return $this->success([
            'list' => $majors->map(fn (EnterpriseCategory $major) => [
                'id' => $major->id,
                'name' => $major->name,
                'sort_order' => $major->sort_order,
                'children' => $major->children->map(fn (EnterpriseCategory $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'sort_order' => $c->sort_order,
                ])->values(),
            ])->values(),
        ]);
    }
}
