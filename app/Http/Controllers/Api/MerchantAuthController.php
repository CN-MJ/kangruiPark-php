<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Merchant;
use App\Services\AuthTokenService;
use App\Services\CaptchaService;
use App\Services\LoginRateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MerchantAuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthTokenService $authTokenService,
        private readonly CaptchaService $captchaService,
        private readonly LoginRateLimiter $rateLimiter,
    ) {}

    public function login(Request $request): JsonResponse
    {
        if (! config('auth_security.merchant.workbench_enabled', false)) {
            return $this->error('商户工作台已停用。企业信息请使用园区工作台「企业库」维护，或联系园区管理员。', 410);
        }

        $minLen = (int) config('auth_security.password.min_length', 6);
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:'.$minLen,
            'captcha_key' => 'required|string|max:64',
            'captcha_code' => 'required|string|max:10',
        ], [
            'phone.required' => '请输入手机号',
            'password.required' => '请输入密码',
            'password.min' => "密码至少 {$minLen} 位",
            'captcha_key.required' => '请刷新图形验证码',
            'captcha_code.required' => '请输入图形验证码',
        ]);

        $rateKey = $this->rateLimiter->key('merchant', $validated['phone'], $request->ip());
        if ($this->rateLimiter->tooManyAttempts($rateKey)) {
            $seconds = $this->rateLimiter->availableIn($rateKey);

            return $this->error("登录尝试过多，请 {$seconds} 秒后再试", 429);
        }

        if (! $this->captchaService->verify($validated['captcha_key'], $validated['captcha_code'])) {
            $this->rateLimiter->hit($rateKey);

            return $this->error('图形验证码错误或已过期', 401);
        }

        $merchant = Merchant::query()
            ->where('contact_phone', $validated['phone'])
            ->where('status', Merchant::STATUS_ACTIVE)
            ->first();

        if (! $merchant) {
            $this->rateLimiter->hit($rateKey);

            return $this->error('该手机号无商户权限', 403);
        }

        if (! $merchant->password || ! Hash::check($validated['password'], $merchant->password)) {
            $this->rateLimiter->hit($rateKey);

            return $this->error('手机号或密码错误', 401);
        }

        $this->rateLimiter->clear($rateKey);

        $token = $this->authTokenService->issueMerchantToken($merchant);

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
        $this->authTokenService->revokeMerchantToken($request->bearerToken());

        return $this->success(null, '已退出登录');
    }
}
