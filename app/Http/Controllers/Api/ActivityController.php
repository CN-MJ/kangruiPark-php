<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Activity;
use App\Models\ActivityRegistration;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $list = Activity::query()
            ->where('status', Activity::STATUS_OPEN)
            ->orderBy('start_time')
            ->get();

        return $this->success($list);
    }

    public function show(int $id): JsonResponse
    {
        $activity = Activity::find($id);
        if (! $activity) {
            return $this->error('活动不存在', 404);
        }

        return $this->success($activity);
    }

    public function register(Request $request, int $id): JsonResponse
    {
        $activity = Activity::find($id);
        if (! $activity) {
            return $this->error('活动不存在', 404);
        }
        if ($activity->status !== Activity::STATUS_OPEN) {
            return $this->error('活动不在报名中', 400);
        }
        if ($activity->max_attendees > 0 && $activity->registered_count >= $activity->max_attendees) {
            return $this->error('报名人数已满', 400);
        }

        $validated = $request->validate([
            'contact_name' => 'required|string|max:50',
            'contact_phone' => 'required|string|max:20',
        ]);

        $existing = ActivityRegistration::withTrashed()
            ->where('activity_id', $activity->id)
            ->where('contact_phone', $validated['contact_phone'])
            ->first();

        if ($existing && ! $existing->trashed()) {
            return $this->error('您已报名该活动', 400);
        }

        if ($existing) {
            $existing->restore();
            $existing->update([
                'contact_name' => $validated['contact_name'],
            ]);
        } else {
            try {
                ActivityRegistration::create([
                    'activity_id' => $activity->id,
                    'contact_name' => $validated['contact_name'],
                    'contact_phone' => $validated['contact_phone'],
                ]);
            } catch (QueryException $e) {
                if (str_contains($e->getMessage(), 'activity_registrations_activity_id_contact_phone_unique')) {
                    return $this->error('您已报名该活动', 400);
                }
                throw $e;
            }
        }

        $activity->increment('registered_count');

        return $this->success($activity->fresh(), '报名成功');
    }
}
