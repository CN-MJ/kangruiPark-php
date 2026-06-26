<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Merchant;
use App\Models\MerchantToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantAuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $merchant = Merchant::query()
            ->where('contact_phone', $validated['phone'])
            ->where('status', Merchant::STATUS_ACTIVE)
            ->first();

        if (! $merchant) {
            return $this->error('该手机号无商户权限', 403);
        }

        $token = bin2hex(random_bytes(32));
        $merchant->tokens()->create([
            'token' => $token,
            'expires_at' => now()->addDays(7),
        ]);

        return $this->success([
            'token' => $token,
            'merchant' => $merchant,
        ], '登录成功');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($request->attributes->get('merchant_user'));
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        if ($token) {
            MerchantToken::query()->where('token', $token)->delete();
        }

        return $this->success(null, '已退出登录');
    }
}
