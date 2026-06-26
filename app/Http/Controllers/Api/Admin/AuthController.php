<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Admin;
use App\Models\AdminToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string|max:100',
        ]);

        $admin = Admin::query()
            ->where('username', $validated['username'])
            ->first();

        if (! $admin || ! Hash::check($validated['password'], $admin->password)) {
            return $this->error('账号或密码错误', 401);
        }

        $token = Str::random(64);

        AdminToken::create([
            'admin_id' => $admin->id,
            'token' => $token,
            'expires_at' => now()->addDays(7),
        ]);

        return $this->success([
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'name' => $admin->name,
            ],
        ], '登录成功');
    }

    public function me(Request $request): JsonResponse
    {
        $admin = $request->attributes->get('admin');

        return $this->success([
            'id' => $admin->id,
            'username' => $admin->username,
            'name' => $admin->name,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if ($token) {
            AdminToken::query()->where('token', $token)->delete();
        }

        return $this->success(null, '已退出登录');
    }
}
