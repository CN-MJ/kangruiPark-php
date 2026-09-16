<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ServiceTeam;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceTeamController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = ServiceTeam::query()
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($request->filled('keyword')) {
            $kw = trim((string) $request->input('keyword'));
            $query->where(function ($q) use ($kw) {
                $q->where('name', 'like', "%{$kw}%")
                    ->orWhere('title', 'like', "%{$kw}%")
                    ->orWhere('phone', 'like', "%{$kw}%");
            });
        }

        $page = $query->paginate(20);
        $page->getCollection()->transform(fn (ServiceTeam $item) => $this->format($item));

        return $this->success($page);
    }

    public function store(Request $request): JsonResponse
    {
        $item = ServiceTeam::create($this->validated($request));

        return $this->success($this->format($item), '创建成功');
    }

    public function show(int $id): JsonResponse
    {
        $item = ServiceTeam::find($id);
        if (! $item) {
            return $this->error('成员不存在', 404);
        }

        return $this->success($this->format($item));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = ServiceTeam::find($id);
        if (! $item) {
            return $this->error('成员不存在', 404);
        }
        $item->update($this->validated($request));

        return $this->success($this->format($item->fresh()), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $item = ServiceTeam::find($id);
        if (! $item) {
            return $this->error('成员不存在', 404);
        }
        $item->delete();

        return $this->success(null, '删除成功');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'title' => 'required|string|max:80',
            'phone' => 'nullable|string|max:30',
            'photo' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_show' => 'nullable|boolean',
        ]);

        if (array_key_exists('photo', $data) && $data['photo']) {
            $data['photo'] = MediaUrl::toRelative($data['photo']) ?: $data['photo'];
        }

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_show'] = array_key_exists('is_show', $data)
            ? (bool) $data['is_show']
            : true;

        return $data;
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
            'is_show' => (bool) $item->is_show,
            'created_at' => optional($item->created_at)?->toDateTimeString(),
            'updated_at' => optional($item->updated_at)?->toDateTimeString(),
        ];
    }
}
