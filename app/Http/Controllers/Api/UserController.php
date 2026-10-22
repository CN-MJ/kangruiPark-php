<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\AuthTokenService;
use App\Services\CaptchaService;
use App\Services\ImageCompressService;
use App\Services\LoginRateLimiter;
use App\Services\WeChat\MiniProgramService;
use App\Support\ParkPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthTokenService $authTokenService,
        private readonly CaptchaService $captchaService,
        private readonly LoginRateLimiter $rateLimiter,
        private readonly MiniProgramService $miniProgram,
        private readonly ImageCompressService $imageCompress,
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
            $trashed = User::onlyTrashed()->where('phone', $validated['phone'])->first();
            if ($trashed) {
                return $this->success([
                    'phone' => $validated['phone'],
                    'name' => $trashed->name ?: '园区访客',
                    'role' => User::ROLE_VISITOR,
                    'role_text' => $this->roleText(User::ROLE_VISITOR),
                    'phone_verified' => false,
                ], '请先微信授权手机号登录');
            }

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

        $user = User::withTrashed()->where('openid', $openid)->first()
            ?: User::withTrashed()->where('phone', $phone)->first();

        // 园区人员也可微信授权拿个人会话（才能看到工作台入口）；工作台仍须单独账号密码登录
        if (! $user) {
            $user = User::query()->create([
                'phone' => $phone,
                'openid' => $openid,
                'name' => $validated['name'] ?? '园区访客',
                'role' => User::ROLE_VISITOR,
                'phone_verified_at' => now(),
            ]);
        } else {
            if ($user->trashed()) {
                $user->restore();
            }
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
        $bearer = $request->bearerToken();
        if ($request->attributes->get('via_super')) {
            $this->authTokenService->revokeSuperToken($bearer);
        } else {
            $this->authTokenService->revokeUserToken($bearer);
        }

        return $this->success(null, '已退出登录');
    }

    /**
     * 访客退出：吊销当前 user_token（客户端再清本地资料）
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authTokenService->revokeUserToken($request->bearerToken());

        return $this->success(null, '已退出登录');
    }

    /**
     * 已登录用户修改名称和头像。头像须是本接口上传后的 uploads/avatars 路径。
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('xcx_user');

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'avatar' => 'nullable|string|max:500',
        ], [
            'name.required' => '请填写名称',
            'name.max' => '名称不能超过 50 个字',
        ]);

        $name = trim($validated['name']);
        if ($name === '') {
            return $this->error('请填写名称', 422);
        }

        $payload = ['name' => $name];

        if (array_key_exists('avatar', $validated)) {
            $raw = $validated['avatar'];
            if ($raw === null || trim((string) $raw) === '') {
                $payload['avatar'] = null;
            } else {
                $relative = $this->normalizeAvatarPath((string) $raw);
                if ($relative === null) {
                    return $this->error('头像地址无效，请重新上传', 422);
                }
                if (! $this->avatarExists($relative)) {
                    return $this->error('头像文件不存在，请重新上传', 422);
                }
                $payload['avatar'] = $relative;
            }
        }

        $previous = $user->avatar;
        $user->update($payload);

        if (
            array_key_exists('avatar', $payload)
            && $previous
            && $previous !== $payload['avatar']
        ) {
            $this->deleteAvatarFile($previous);
        }

        return $this->success($this->formatUser($user->fresh()), '已保存');
    }

    /**
     * 上传头像。限制与企业图片一致：上传上限 8MB，压缩后不超过 2MB。
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $uploadMaxKb = 8192;
        $hardMaxBytes = 2_097_152;
        $softBytes = 1_048_576;

        $request->validate([
            'file' => "required|image|mimes:jpeg,png,jpg,webp|max:{$uploadMaxKb}",
        ], [
            'file.required' => '请选择头像',
            'file.image' => '头像必须是图片',
            'file.mimes' => '头像仅支持 jpg、png、webp',
            'file.max' => '图片不能超过 8MB',
        ]);

        $stored = $request->file('file')->store('avatars', 'local');
        $absolute = Storage::disk('local')->path($stored);

        $result = $this->imageCompress->compressIfNeeded($absolute, $hardMaxBytes, $softBytes, 1920);

        if ($result['final_size'] > $hardMaxBytes) {
            foreach (array_unique([$result['path'], $absolute]) as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }

            return $this->error('图片过大，压缩后仍超过 2MB，请换一张更小的图', 422);
        }

        $relative = $this->toDiskRelative('local', $result['path']) ?: $stored;
        if (! str_starts_with($relative, 'avatars/')) {
            $this->deleteAvatarFile($relative);
            $this->deleteAvatarFile($stored);

            return $this->error('头像保存失败', 500);
        }

        return $this->success([
            'path' => $relative,
            'compressed' => $result['compressed'],
            'original_size' => $result['original_size'],
            'final_size' => $result['final_size'],
        ], $result['compressed'] ? '上传成功（已自动压缩）' : '上传成功');
    }

    /**
     * 读取当前登录用户的头像。文件不在网站公开目录，必须携带用户 Token。
     */
    public function showAvatar(Request $request): Response|JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('xcx_user');
        $relative = $this->protectAvatar($user);
        if ($relative === null) {
            return $this->error('尚未设置头像', 404);
        }

        return Storage::disk('local')->response($relative, null, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * 登录页探测：是否允许开发 Mock（生产恒为 false）
     */
    public function loginOptions(): JsonResponse
    {
        return $this->success([
            'mock_enabled' => $this->miniProgram->isMock(),
            'merchant_workbench_enabled' => (bool) config('auth_security.merchant.workbench_enabled', false),
        ]);
    }

    /**
     * 已登录的普通用户申请成为工作人员，由管理员或超管审核并分配岗位。
     */
    public function staffApply(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('xcx_user');

        if ($user->isPark()) {
            return $this->error('你已是工作人员', 422);
        }
        if ($user->staff_apply_status === User::APPLY_PENDING) {
            return $this->error('申请正在审核中', 422);
        }

        $validated = $request->validate([
            'note' => 'nullable|string|max:200',
        ]);

        $note = trim((string) ($validated['note'] ?? ''));
        $user->staff_apply_status = User::APPLY_PENDING;
        $user->staff_apply_note = $note !== '' ? $note : null;
        $user->staff_applied_at = now();
        $user->staff_reviewed_at = null;
        $user->staff_reject_reason = null;
        $user->save();

        return $this->success($this->formatUser($user->fresh()), '申请已提交，请等待管理员审核');
    }

    public function parkMe(Request $request): JsonResponse
    {
        $user = $request->attributes->get('park_user');

        return $this->success($this->formatUser($user));
    }

    private function formatUser(User $user): array
    {
        $data = [
            'id' => $user->id,
            'phone' => $user->phone,
            'name' => $user->name,
            'has_avatar' => $this->protectAvatar($user) !== null,
            'role' => $user->role,
            'role_text' => $this->roleText($user->role),
            'checked_in_at' => $user->checked_in_at,
            'phone_verified' => $user->phone_verified_at !== null,
            'merchant_apply_status' => $user->merchant_apply_status ?: User::APPLY_NONE,
            'merchant_applied_at' => $user->merchant_applied_at,
            'staff_apply_status' => $user->staff_apply_status ?: User::APPLY_NONE,
            'staff_apply_status_text' => $this->staffStatusText($user->staff_apply_status ?: User::APPLY_NONE),
            'staff_apply_note' => $user->staff_apply_note,
            'staff_reject_reason' => $user->staff_reject_reason,
        ];

        if ($user->isPark() || $user->canEscalate()) {
            $parkRole = ParkPermissions::normalizeRole($user->park_role ?? null);
            // 超管升权进工作台时按管理员能力展示
            if ($user->canEscalate() && ! $user->isPark()) {
                $parkRole = ParkPermissions::ROLE_ADMIN;
            }
            $data['park_role'] = $parkRole;
            $data['park_role_text'] = ParkPermissions::roleLabels()[$parkRole] ?? $parkRole;
            $data['permissions'] = ParkPermissions::permissionsForRole($parkRole);
        }

        return $data;
    }

    private function normalizeAvatarPath(string $value): ?string
    {
        $value = trim(str_replace('\\', '/', $value));
        if (preg_match('#^https?://#i', $value)) {
            $value = parse_url($value, PHP_URL_PATH) ?: '';
        }
        $value = ltrim($value, '/');
        if (str_starts_with($value, 'storage/')) {
            $value = substr($value, strlen('storage/'));
        }
        if ($value === '' || str_contains($value, '..')) {
            return null;
        }
        if (str_starts_with($value, 'avatars/') || str_starts_with($value, 'uploads/avatars/')) {
            return $value;
        }

        return null;
    }

    /**
     * 把头像留在 storage/app/avatars，并删掉曾经放到公开目录的副本。
     * 成功时返回私有相对路径。
     */
    private function protectAvatar(User $user): ?string
    {
        $path = (string) $user->avatar;
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }
        if (str_starts_with($path, 'avatars/') && Storage::disk('local')->exists($path)) {
            return $path;
        }
        if (str_starts_with($path, 'uploads/avatars/') && Storage::disk('public')->exists($path)) {
            $target = 'avatars/'.basename($path);
            Storage::disk('local')->put($target, Storage::disk('public')->get($path));
            Storage::disk('public')->delete($path);
            $user->update(['avatar' => $target]);

            return $target;
        }

        return null;
    }

    private function avatarExists(string $relative): bool
    {
        if (str_starts_with($relative, 'avatars/')) {
            return Storage::disk('local')->exists($relative);
        }
        if (str_starts_with($relative, 'uploads/avatars/')) {
            return Storage::disk('public')->exists($relative);
        }

        return false;
    }

    private function deleteAvatarFile(?string $relative): void
    {
        if (! $relative || str_contains($relative, '..')) {
            return;
        }
        if (str_starts_with($relative, 'avatars/')) {
            Storage::disk('local')->delete($relative);
        }
        if (str_starts_with($relative, 'uploads/avatars/')) {
            Storage::disk('public')->delete($relative);
        }
    }

    private function toDiskRelative(string $disk, string $absolutePath): ?string
    {
        $root = str_replace('\\', '/', Storage::disk($disk)->path(''));
        $root = rtrim($root, '/').'/';
        $abs = str_replace('\\', '/', $absolutePath);
        if (! str_starts_with($abs, $root)) {
            return null;
        }

        return ltrim(substr($abs, strlen($root)), '/');
    }

    private function roleText(string $role): string
    {
        return match ($role) {
            User::ROLE_MERCHANT => '商户',
            User::ROLE_PARK => '工作人员',
            default => '访客',
        };
    }

    private function staffStatusText(string $status): string
    {
        return match ($status) {
            User::APPLY_PENDING => '待审核',
            User::APPLY_APPROVED => '已通过',
            User::APPLY_REJECTED => '已拒绝',
            default => '未申请',
        };
    }
}
