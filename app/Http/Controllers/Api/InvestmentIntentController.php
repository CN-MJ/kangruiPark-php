<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\BuildingHouse;
use App\Models\InvestmentIntent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestmentIntentController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:120',
            'contact_name' => 'required|string|max:50',
            'contact_phone' => 'required|string|max:20',
            'demand_area' => 'required|string|max:50',
            'house_id' => 'nullable|integer|exists:building_houses,id',
            'message' => 'nullable|string|max:500',
            'source' => 'nullable|string|max:30',
        ]);

        if (! empty($validated['house_id'])) {
            $house = BuildingHouse::query()->find($validated['house_id']);
            if (! $house || $house->status !== BuildingHouse::STATUS_ON_MARKET) {
                return $this->error('所选房源当前不可登记', 400);
            }
        }

        $intent = InvestmentIntent::create([
            ...$validated,
            'status' => InvestmentIntent::STATUS_PENDING,
            'source' => $validated['source'] ?? '小程序',
        ]);

        return $this->success($intent, '提交成功，招商顾问会尽快联系您');
    }
}
