<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    use ApiResponse;

    /**
     * 新闻/公告列表（置顶优先，按发布时间倒序）
     * type=news 园区动态（默认）| type=notice 首页公告
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $type = $request->input('type', News::TYPE_NEWS);

        $query = News::query()
            ->orderByDesc('is_top')
            ->orderByDesc('publish_time');

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $news = $query->paginate($perPage);

        return $this->success([
            'list' => $news->items(),
            'pagination' => [
                'current_page' => $news->currentPage(),
                'per_page' => $news->perPage(),
                'total' => $news->total(),
                'last_page' => $news->lastPage(),
            ],
        ]);
    }

    /**
     * 新闻详情
     */
    public function show(int $id): JsonResponse
    {
        $news = News::find($id);

        if (! $news) {
            return $this->error('新闻不存在', 404);
        }

        return $this->success($news);
    }
}
