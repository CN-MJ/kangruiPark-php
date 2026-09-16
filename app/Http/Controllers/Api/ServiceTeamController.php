<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ServiceTeam;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;

class ServiceTeamController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $list = ServiceTeam::query()
            ->where('is_show', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ServiceTeam $item) => $this->format($item))
            ->values();

        return $this->success(['list' => $list]);
    }

    public function show(int $id): JsonResponse
    {
        $item = ServiceTeam::query()->where('is_show', true)->find($id);
        if (! $item) {
            return $this->error('成员不存在', 404);
        }

        return $this->success($this->format($item));
    }

    private function format(ServiceTeam $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'title' => $item->title,
            'phone' => $item->phone,
            'photo' => MediaUrl::absolute($item->photo, request()),
            'sort_order' => $item->sort_order,
        ];
    }
}
