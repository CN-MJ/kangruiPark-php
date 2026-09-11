<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\AuthTokenService;
use App\Services\CaptchaService;
use App\Services\LoginRateLimiter;
use App\Services\WeChat\MiniProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthTokenService $authTokenService,
        private readonly CaptchaService $captchaService,
        private readonly LoginRateLimiter $rateLimiter,
        private readonly MiniProgramService $miniProgram,
    ) {}

    /**
     * 同步资料（不再凭手机号签发 Token，防止冒充）
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'name' => 'nullable|string|max:50',
        ]);

        $user = User::query()->where('phone', $validated['phone'])->first();

        if (! $user) {
            return $this->success([
                'phone' => $validated['phone'],
                'name' => $validated['name'] ?? '园区访客',
                'role' => User::ROLE_VISITOR,
                'role_text' => $this->roleText(User::ROLE_VISITOR),
                'phone_verified' => false,
            ], '请先微信授权手机号登录');
        }

        if (! empty($validated['name']) && $user->name !== $validated['name']) {
            $user->update(['name' => $validated['name']]);
            $user = $user->fresh();
        }

        return $this->success($this->formatUser($user), 'ok');
    }

    /**
     * 微信授权手机号登录（访客端）
     */
    public function wechatPhoneLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login_code' => 'required|string|max:128',
            'phone_code' => 'required|string|max:256',
            'name' => 'nullable|string|max:50',
        ], [
            'login_code.required' => '缺少微信登录凭证',
            'phone_code.required' => '缺少手机号授权凭证',
        ]);

        try {
            $session = $this->miniProgram->codeToSession($validated['login_code']);
            $phone = $this->miniProgram->phoneFromCode($validated['phone_code']);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }

        if ($phone === '') {
            return $this->error('未获取到手机号', 400);
        }

        $openid = $session['openid'];

        $user = User::query()->where('openid', $openid)->first()
            ?: User::query()->where('phone', $phone)->first();

        if ($user && $user->isPark()) {
            return $this->error('园区人员请从「园区工作台」登录', 403);
        }

        if (! $user) {
            $user = User::query()->create([
                'phone' => $phone,
                'openid' => $openid,
                'name' => $validated['name'] ?? '园区访客',
                'role' => User::ROLE_VISITOR,
                'phone_verified_at' => now(),
            ]);
        } else {
            $user->update([
                'phone' => $phone,
                'openid' => $openid,
                'name' => $validated['name'] ?? $user->name,
                'phone_verified_at' => now(),
            ]);
            $user = $user->fresh();
        }

        $token = $this->authTokenService->issueUserToken($user);

        return $this->success(array_merge($this->formatUser($user), [
            'token' => $token,
            'wechat_mock' => $this->miniProgram->isMock(),
        ]), '登录成功');
    }

    /**
     * 旧入驻申请已停用：企业录入请在园区工作台「企业库」操作
     */
    public function checkin(Request $request): JsonResponse
    {
        return $this->error('入驻申请已停用，请登录园区工作台在「企业库」录入企业', 410);
    }

    /**
     * 园区人员登录：手机号 + 密码 + 图形验证码
     */
    public function parkLogin(Request $request): JsonResponse
    {
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

        $rateKey = $this->rateLimiter->key('park', $validated['phone'], $request->ip());
        if ($this->rateLimiter->tooManyAttempts($rateKey)) {
            $seconds = $this->rateLimiter->availableIn($rateKey);

            return $this->error("登录尝试过多，请 {$seconds} 秒后再试", 429);
        }

        if (! $this->captchaService->verify($validated['captcha_key'], $validated['captcha_code'])) {
            $this->rateLimiter->hit($rateKey);

            return $this->error('图形验证码错误或已过期', 401);
        }

        $user = User::query()->where('phone', $validated['phone'])->first();

        if (! $user || ! $user->isPark()) {
            $this->rateLimiter->hit($rateKey);

            return $this->error('该手机号无园区管理权限', 403);
        }

        if (! $user->password || ! Hash::check($validated['password'], $user->password)) {
            $this->rateLimiter->hit($rateKey);

            return $this->error('手机号或密码错误', 401);
        }

        $this->rateLimiter->clear($rateKey);

        $token = $this->authTokenService->issueUserToken($user);

        return $this->success([
            'token' => $token,
            'user' => $this->formatUser($user),
        ], '登录成功');
    }

    public function parkLogout(Request $request): JsonResponse
    {
        $this->authTokenService->revokeUserToken($request->bearerToken());

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
            'phone_verified' => $user->phone_verified_at !== null,
            'merchant_apply_status' => $user->merchant_apply_status ?: User::APPLY_NONE,
            'merchant_applied_at' => $user->merchant_applied_at,
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
