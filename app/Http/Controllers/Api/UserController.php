<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\UserToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * 同步用户信息（访客默认识别）
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'name' => 'nullable|string|max:50',
        ]);

        $user = User::query()->firstOrCreate(
            ['phone' => $validated['phone']],
            [
                'name' => $validated['name'] ?? '园区访客',
                'role' => User::ROLE_VISITOR,
            ]
        );

        if (! empty($validated['name']) && $user->name !== $validated['name']) {
            $user->update(['name' => $validated['name']]);
        }

        return $this->success($this->formatUser($user));
    }

    /**
     * 入住签到：访客 → 商户
     */
    public function checkin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'name' => 'nullable|string|max:50',
        ]);

        $user = User::query()->firstOrCreate(
            ['phone' => $validated['phone']],
            [
                'name' => $validated['name'] ?? '园区访客',
                'role' => User::ROLE_VISITOR,
            ]
        );

        if ($user->isPark()) {
            return $this->error('园区账号无需签到', 400);
        }

        $user->update([
            'name' => $validated['name'] ?? $user->name,
            'role' => User::ROLE_MERCHANT,
            'checked_in_at' => now(),
        ]);

        return $this->success($this->formatUser($user->fresh()), '入住签到成功，您已成为商户');
    }

    /**
     * 园区人员登录（仅 role=park）
     */
    public function parkLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $user = User::query()->where('phone', $validated['phone'])->first();

        if (! $user || ! $user->isPark()) {
            return $this->error('该手机号无园区管理权限', 403);
        }

        $token = bin2hex(random_bytes(32));

        $user->tokens()->create([
            'token' => $token,
            'expires_at' => now()->addDays(7),
        ]);

        return $this->success([
            'token' => $token,
            'user' => $this->formatUser($user),
        ], '登录成功');
    }

    public function parkLogout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if ($token) {
            UserToken::query()->where('token', $token)->delete();
        }

        return $this->success(null, '已退出登录');
    }

    public function parkMe(Request $request): JsonResponse
    {
        $user = $request->attributes->get('park_user');

        return $this->success($this->formatUser($user));
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'phone' => $user->phone,
            'name' => $user->name,
            'role' => $user->role,
            'role_text' => $this->roleText($user->role),
            'checked_in_at' => $user->checked_in_at,
        ];
    }

    private function roleText(string $role): string
    {
        return match ($role) {
            User::ROLE_MERCHANT => '商户',
            User::ROLE_PARK => '园区',
            default => '访客',
        };
    }
}
