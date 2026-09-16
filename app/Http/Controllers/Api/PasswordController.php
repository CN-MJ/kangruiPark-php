<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Merchant;
use App\Models\User;
use App\Services\AuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthTokenService $authTokenService,
    ) {}

    public function changeParkPassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('park_user');
        $minLen = (int) config('auth_security.password.min_length', 8);

        $validated = $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:'.$minLen.'|confirmed',
        ], [
            'new_password.min' => "新密码至少 {$minLen} 位",
            'new_password.confirmed' => '两次输入的新密码不一致',
        ]);

        if (! $user->password || ! Hash::check($validated['old_password'], $user->password)) {
            return $this->error('原密码错误', 400);
        }

        $user->update(['password' => $validated['new_password']]);
        $this->authTokenService->revokeOtherUserTokens($user, $request->bearerToken());

        return $this->success(null, '密码修改成功，其它设备已下线');
    }

    public function changeMerchantPassword(Request $request): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = $request->attributes->get('merchant_user');
        $minLen = (int) config('auth_security.password.min_length', 8);

        $validated = $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:'.$minLen.'|confirmed',
        ], [
            'new_password.min' => "新密码至少 {$minLen} 位",
            'new_password.confirmed' => '两次输入的新密码不一致',
        ]);

        if (! $merchant->password || ! Hash::check($validated['old_password'], $merchant->password)) {
            return $this->error('原密码错误', 400);
        }

        $merchant->update(['password' => $validated['new_password']]);
        $this->authTokenService->revokeOtherMerchantTokens($merchant, $request->bearerToken());

        return $this->success(null, '密码修改成功，其它设备已下线');
    }
}
