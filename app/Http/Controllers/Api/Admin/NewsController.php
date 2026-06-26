<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = News::query()
            ->orderByDesc('is_top')
            ->orderByDesc('publish_time');

        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }

        $news = $query->paginate(20);

        $list = collect($news->items())->map(function (News $item) {
            $data = $item->toArray();
            $data['summary'] = mb_substr(strip_tags($item->content), 0, 80);
            $data['type_text'] = $item->typeText();
            unset($data['content']);

            return $data;
        });

        return $this->success([
            'list' => $list,
            'pagination' => [
                'current_page' => $news->currentPage(),
                'per_page' => $news->perPage(),
                'total' => $news->total(),
                'last_page' => $news->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $news = News::find($id);

        if (! $news) {
            return $this->error('新闻不存在', 404);
        }

        $data = $news->toArray();
        $data['type_text'] = $news->typeText();

        return $this->success($data);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'type' => 'nullable|string|in:news,notice',
            'content' => 'required|string',
            'cover_image' => 'nullable|string|max:500',
            'publish_time' => 'nullable|date',
            'is_top' => 'nullable|boolean',
        ]);

        $type = $validated['type'] ?? News::TYPE_NEWS;

        $news = News::create([
            'title' => $validated['title'],
            'type' => $type,
            'content' => $validated['content'],
            'cover_image' => $type === News::TYPE_NOTICE ? null : ($validated['cover_image'] ?? null),
            'publish_time' => $validated['publish_time'] ?? now(),
            'is_top' => filter_var($validated['is_top'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);

        return $this->success($news, $type === News::TYPE_NOTICE ? '公告发布成功' : '发布成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $news = News::find($id);

        if (! $news) {
            return $this->error('新闻不存在', 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'type' => 'nullable|string|in:news,notice',
            'content' => 'required|string',
            'cover_image' => 'nullable|string|max:500',
            'publish_time' => 'nullable|date',
            'is_top' => 'nullable|boolean',
        ]);

        $type = $validated['type'] ?? $news->type;

        $news->update([
            'title' => $validated['title'],
            'type' => $type,
            'content' => $validated['content'],
            'cover_image' => $type === News::TYPE_NOTICE ? null : ($validated['cover_image'] ?? null),
            'publish_time' => $validated['publish_time'] ?? $news->publish_time,
            'is_top' => filter_var($validated['is_top'] ?? $news->is_top, FILTER_VALIDATE_BOOLEAN),
        ]);

        return $this->success($news, '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $news = News::find($id);

        if (! $news) {
            return $this->error('新闻不存在', 404);
        }

        $news->delete();

        return $this->success(null, '删除成功');
    }

    public function toggleTop(int $id): JsonResponse
    {
        $news = News::find($id);

        if (! $news) {
            return $this->error('新闻不存在', 404);
        }

        $news->update(['is_top' => ! $news->is_top]);

        return $this->success($news, $news->is_top ? '已置顶' : '已取消置顶');
    }
}
