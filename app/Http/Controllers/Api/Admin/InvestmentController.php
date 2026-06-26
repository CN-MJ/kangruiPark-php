<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\InvestmentIntent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = InvestmentIntent::query()
            ->with('house:id,title')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }
        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        return $this->success($query->paginate(20));
    }

    public function show(int $id): JsonResponse
    {
        $intent = InvestmentIntent::query()
            ->with('house:id,title')
            ->find($id);
        if (! $intent) {
            return $this->error('意向记录不存在', 404);
        }

        return $this->success($intent);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $intent = InvestmentIntent::find($id);
        if (! $intent) {
            return $this->error('意向记录不存在', 404);
        }

        $validated = $request->validate([
            'status' => 'required|integer|in:0,1,2,3,4',
        ]);

        $intent->update(['status' => $validated['status']]);

        return $this->success($intent, '状态更新成功');
    }
}
