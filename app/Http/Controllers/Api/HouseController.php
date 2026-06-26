<?php

namespace App\Http\Controllers\Api;

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
        $query = BuildingHouse::query()
            ->orderByDesc('sort_order')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        } else {
            $query->where('status', BuildingHouse::STATUS_ON_MARKET);
        }

        return $this->success($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $house = BuildingHouse::find($id);
        if (! $house) {
            return $this->error('房源不存在', 404);
        }

        return $this->success($house);
    }
}
