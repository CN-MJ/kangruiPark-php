<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Activity;
use App\Models\AdminAuditLog;
use App\Models\Appointment;
use App\Models\BuildingHouse;
use App\Models\InvestmentIntent;
use App\Models\News;
use App\Models\ParkingRecord;
use App\Models\Policy;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\AuthTokenService;
use App\Services\CaptchaService;
use App\Services\LoginRateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperConsoleController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthTokenService $authTokenService,
        private readonly CaptchaService $captchaService,
        private readonly LoginRateLimiter $rateLimiter,
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    /**
     * 探测当前访客是否具备升权资格（不泄露其它信息）
     */
    public function probe(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('xcx_user');

        return $this->success([
            'can_escalate' => $user->canEscalate(),
        ]);
    }

    /**
     * 二次验证后升权：密码 + 图形验证码
     */
    public function escalate(Request $request): JsonResponse
    {
        if (! config('auth_security.super.enabled', true)) {
            return $this->error('功能未启用', 403);
        }

        /** @var User $user */
        $user = $request->attributes->get('xcx_user');

        if (! $user->canEscalate()) {
            return $this->error('无权限', 403);
        }

        $allowlist = trim((string) config('auth_security.super.ip_allowlist', ''));
        if ($allowlist !== '') {
            $allowed = array_filter(array_map('trim', explode(',', $allowlist)));
            if ($allowed && ! in_array($request->ip(), $allowed, true)) {
                return $this->error('当前网络不允许进入控制台', 403);
            }
        }

        $minLen = (int) config('auth_security.password.min_length', 6);
        $validated = $request->validate([
            'password' => 'required|string|min:'.$minLen,
            'captcha_key' => 'required|string|max:64',
            'captcha_code' => 'required|string|max:10',
        ], [
            'password.required' => '请输入密码',
            'captcha_key.required' => '请刷新图形验证码',
            'captcha_code.required' => '请输入图形验证码',
        ]);

        $rateKey = $this->rateLimiter->key('super', $user->phone, $request->ip());
        if ($this->rateLimiter->tooManyAttempts($rateKey)) {
            $seconds = $this->rateLimiter->availableIn($rateKey);

            return $this->error("尝试过多，请 {$seconds} 秒后再试", 429);
        }

        if (! $this->captchaService->verify($validated['captcha_key'], $validated['captcha_code'])) {
            $this->rateLimiter->hit($rateKey);
            $this->auditLogger->log($user, 'super.escalate_failed', 'user', (string) $user->id, [
                'reason' => 'bad_captcha',
            ], $request);

            return $this->error('图形验证码错误或已过期', 401);
        }

        if (! $user->password || ! Hash::check($validated['password'], $user->password)) {
            $this->rateLimiter->hit($rateKey);
            $this->auditLogger->log($user, 'super.escalate_failed', 'user', (string) $user->id, [
                'reason' => 'bad_password',
            ], $request);

            return $this->error('密码错误', 401);
        }

        $this->rateLimiter->clear($rateKey);
        $token = $this->authTokenService->issueSuperToken($user);
        $this->auditLogger->log($user, 'super.escalate', 'user', (string) $user->id, null, $request);

        return $this->success([
            'token' => $token,
            'expires_in_hours' => (int) config('auth_security.super.token_ttl_hours', 4),
            'user' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'name' => $user->name,
            ],
        ], '验证成功');
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('super_user');
        $this->authTokenService->revokeSuperToken($request->bearerToken());
        $this->auditLogger->log($user, 'super.logout', 'user', (string) $user->id, null, $request);

        return $this->success(null, '已退出控制台');
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('super_user');

        return $this->success([
            'id' => $user->id,
            'phone' => $user->phone,
            'name' => $user->name,
            'role' => $user->role,
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $totalSpots = (int) config('parking.total_spots', 100);
        $occupied = ParkingRecord::where('status', ParkingRecord::STATUS_IN_PARK)->count();

        return $this->success([
            'users_total' => User::count(),
            'visitors_count' => User::where('role', User::ROLE_VISITOR)->count(),
            'merchants_count' => User::where('role', User::ROLE_MERCHANT)->count(),
            'park_staff_count' => User::where('role', User::ROLE_PARK)->count(),
            'appointments_total' => Appointment::count(),
            'appointments_pending' => Appointment::where('status', Appointment::STATUS_PENDING)->count(),
            'parking_occupied' => $occupied,
            'parking_remaining' => max(0, $totalSpots - $occupied),
            'news_total' => News::count(),
            'houses_total' => BuildingHouse::count(),
            'policies_total' => Policy::count(),
            'activities_total' => Activity::count(),
            'investments_pending' => InvestmentIntent::where('status', InvestmentIntent::STATUS_PENDING)->count(),
            'investments_total' => InvestmentIntent::count(),
            'audit_logs_total' => AdminAuditLog::count(),
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $query = User::query()->orderByDesc('id');
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }
        if ($request->filled('keyword')) {
            $kw = $request->input('keyword');
            $query->where(function ($q) use ($kw) {
                $q->where('phone', 'like', "%{$kw}%")
                    ->orWhere('name', 'like', "%{$kw}%");
            });
        }

        $paginator = $query->paginate(20);
        $paginator->getCollection()->transform(function (User $item) {
            return [
                'id' => $item->id,
                'phone' => $item->phone,
                'name' => $item->name,
                'role' => $item->role,
                'phone_verified' => $item->phone_verified_at !== null,
                'checked_in_at' => $item->checked_in_at,
                'created_at' => $item->created_at,
            ];
        });

        return $this->success($paginator);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->attributes->get('super_user');

        $target = User::find($id);
        if (! $target) {
            return $this->error('用户不存在', 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:50',
            'role' => 'nullable|string|in:visitor,merchant,park',
        ]);

        $before = [
            'name' => $target->name,
            'role' => $target->role,
        ];

        $target->fill(array_filter($validated, fn ($v) => $v !== null));
        $target->save();

        $after = [
            'name' => $target->name,
            'role' => $target->role,
        ];
        $changes = [];
        foreach ($after as $key => $value) {
            if (($before[$key] ?? null) !== $value) {
                $changes[$key] = ['from' => $before[$key] ?? null, 'to' => $value];
            }
        }

        $this->auditLogger->log($actor, 'user.update', 'user', (string) $target->id, [
            'title' => '修改用户 #'.$target->id,
            'operation' => 'update',
            'resource' => 'user',
            'resource_label' => '用户',
            'before' => $before,
            'after' => $after,
            'changes' => $changes,
        ], $request);

        return $this->success([
            'id' => $target->id,
            'phone' => $target->phone,
            'name' => $target->name,
            'role' => $target->role,
        ], '已更新');
    }

    public function auditLogs(Request $request): JsonResponse
    {
        // 只读：无 update/delete 路由；模型与数据库触发器均禁止改删
        $perPage = min(50, max(10, (int) $request->query('per_page', 15)));

        $list = AdminAuditLog::query()
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->success($list);
    }
}
