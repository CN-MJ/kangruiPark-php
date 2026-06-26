<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Policy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Policy::query()->orderByDesc('sort_order')->orderByDesc('id');
        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }

        return $this->success($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $policy = Policy::create($this->validateData($request));
        return $this->success($policy, '创建成功');
    }

    public function show(int $id): JsonResponse
    {
        $policy = Policy::find($id);
        if (! $policy) {
            return $this->error('政策不存在', 404);
        }

        return $this->success($policy);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $policy = Policy::find($id);
        if (! $policy) {
            return $this->error('政策不存在', 404);
        }
        $policy->update($this->validateData($request));

        return $this->success($policy, '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $policy = Policy::find($id);
        if (! $policy) {
            return $this->error('政策不存在', 404);
        }
        $policy->delete();

        return $this->success(null, '删除成功');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:180',
            'level' => 'required|string|max:20',
            'content' => 'required|string',
            'cover_image' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_show' => 'nullable|boolean',
        ]);
    }
}
