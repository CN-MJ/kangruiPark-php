<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\BuildingHouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HouseController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = BuildingHouse::query()->orderByDesc('sort_order')->orderByDesc('id');
        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        return $this->success($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateData($request);
        $house = BuildingHouse::create($validated);

        return $this->success($house, '创建成功');
    }

    public function show(int $id): JsonResponse
    {
        $house = BuildingHouse::find($id);
        if (! $house) {
            return $this->error('房源不存在', 404);
        }

        return $this->success($house);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $house = BuildingHouse::find($id);
        if (! $house) {
            return $this->error('房源不存在', 404);
        }

        $house->update($this->validateData($request));
        return $this->success($house, '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $house = BuildingHouse::find($id);
        if (! $house) {
            return $this->error('房源不存在', 404);
        }
        $house->delete();

        return $this->success(null, '删除成功');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:120',
            'area' => 'required|string|max:50',
            'price' => 'required|string|max:50',
            'house_type' => 'required|string|max:50',
            'description' => 'required|string',
            'images' => 'nullable|array',
            'status' => 'nullable|integer|in:0,1',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
