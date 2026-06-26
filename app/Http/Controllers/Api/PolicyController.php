<?php

namespace App\Http\Controllers\Api;

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
        $query = Policy::query()
            ->where('is_show', true)
            ->orderByDesc('sort_order')
            ->orderByDesc('id');

        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }

        return $this->success($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $policy = Policy::query()->where('is_show', true)->find($id);
        if (! $policy) {
            return $this->error('政策不存在', 404);
        }

        return $this->success($policy);
    }
}
