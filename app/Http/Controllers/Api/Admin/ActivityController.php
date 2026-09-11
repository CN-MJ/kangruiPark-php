<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Activity;
use App\Models\ActivityRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Activity::query()->orderByDesc('start_time');
        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        return $this->success($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $activity = Activity::create($this->validateData($request));
        return $this->success($activity, '创建成功');
    }

    public function show(int $id): JsonResponse
    {
        $activity = Activity::query()->withCount('registrations')->find($id);
        if (! $activity) {
            return $this->error('活动不存在', 404);
        }

        return $this->success($activity);
    }

    public function registrations(int $id): JsonResponse
    {
        $activity = Activity::find($id);
        if (! $activity) {
            return $this->error('活动不存在', 404);
        }

        $list = ActivityRegistration::query()
            ->where('activity_id', $activity->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->success([
            'activity' => [
                'id' => $activity->id,
                'title' => $activity->title,
                'registered_count' => $activity->registered_count,
                'max_attendees' => $activity->max_attendees,
                'start_time' => $activity->start_time,
                'location' => $activity->location,
            ],
            'list' => $list,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $activity = Activity::find($id);
        if (! $activity) {
            return $this->error('活动不存在', 404);
        }

        $activity->update($this->validateData($request));
        return $this->success($activity, '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $activity = Activity::find($id);
        if (! $activity) {
            return $this->error('活动不存在', 404);
        }
        $activity->delete();

        return $this->success(null, '删除成功');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:180',
            'cover_image' => 'nullable|string|max:500',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',
            'location' => 'required|string|max:120',
            'description' => 'required|string',
            'max_attendees' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|in:0,1,2',
        ]);
    }
}
