<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ParkingRecord;
use App\Models\User;
use App\Models\UserVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserVehicleController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('xcx_user');

        $list = UserVehicle::query()
            ->where('user_id', $user->id)
            ->where('status', UserVehicle::STATUS_ACTIVE)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return $this->success($list);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('xcx_user');

        $validated = $request->validate([
            'plate_number' => 'required|string|max:20',
            'is_default' => 'nullable|boolean',
        ], [
            'plate_number.required' => '请输入车牌号',
        ]);

        $plateKey = UserVehicle::normalizePlate($validated['plate_number']);

        if ($plateKey === '') {
            return $this->error('请输入有效的车牌号', 400);
        }

        $isDefault = (bool) ($validated['is_default'] ?? false);

        try {
            $vehicle = DB::transaction(function () use ($user, $plateKey, $isDefault) {
                $existing = UserVehicle::query()
                    ->where('user_id', $user->id)
                    ->where('plate_number', $plateKey)
                    ->first();

                if ($existing && (int) $existing->status === UserVehicle::STATUS_ACTIVE) {
                    throw new \RuntimeException('DUPLICATE');
                }

                if ($existing) {
                    $existing->update(['status' => UserVehicle::STATUS_ACTIVE]);
                    $vehicle = $existing;
                } else {
                    $vehicle = UserVehicle::create([
                        'user_id' => $user->id,
                        'plate_number' => $plateKey,
                        'is_default' => false,
                        'status' => UserVehicle::STATUS_ACTIVE,
                    ]);
                }

                if ($isDefault) {
                    $this->setDefaultVehicle($user, $vehicle);
                } elseif (! UserVehicle::query()->where('user_id', $user->id)->where('is_default', true)->where('status', UserVehicle::STATUS_ACTIVE)->exists()) {
                    $vehicle->update(['is_default' => true]);
                }

                return $vehicle->fresh();
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'DUPLICATE') {
                return $this->error('该车牌已绑定', 400);
            }
            throw $e;
        }

        return $this->success($vehicle, '绑定成功');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('xcx_user');

        $vehicle = UserVehicle::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $vehicle) {
            return $this->error('车辆不存在', 404);
        }

        $wasDefault = (bool) $vehicle->is_default;
        $vehicle->update(['status' => UserVehicle::STATUS_INACTIVE, 'is_default' => false]);

        if ($wasDefault) {
            $next = UserVehicle::query()
                ->where('user_id', $user->id)
                ->where('status', UserVehicle::STATUS_ACTIVE)
                ->orderByDesc('id')
                ->first();

            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return $this->success(null, '解绑成功');
    }

    public function setDefault(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('xcx_user');

        $vehicle = UserVehicle::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->where('status', UserVehicle::STATUS_ACTIVE)
            ->first();

        if (! $vehicle) {
            return $this->error('车辆不存在', 404);
        }

        DB::transaction(function () use ($user, $vehicle) {
            $this->setDefaultVehicle($user, $vehicle);
        });

        return $this->success($vehicle->fresh(), '已设为默认车辆');
    }

    private function setDefaultVehicle(User $user, UserVehicle $vehicle): void
    {
        UserVehicle::query()
            ->where('user_id', $user->id)
            ->where('status', UserVehicle::STATUS_ACTIVE)
            ->update(['is_default' => false]);

        $vehicle->update(['is_default' => true]);
    }
}
