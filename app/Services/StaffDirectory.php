<?php

namespace App\Services;

use App\Models\ParkRole;
use App\Models\User;
use App\Support\ParkPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffDirectory
{
    public function __construct(
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function listUsers(Request $request): array
    {
        $this->ensureReady();
        $query = User::query()
            ->orderByRaw("CASE staff_apply_status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('staff_applied_at')
            ->orderByDesc('id');

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }
        if ($request->filled('park_role')) {
            $query->where('role', User::ROLE_PARK)->where('park_role', $request->input('park_role'));
        }
        if ($request->filled('staff_apply_status')) {
            $query->where('staff_apply_status', $request->input('staff_apply_status'));
        }
        if ($request->filled('apply_status')) {
            $query->where('merchant_apply_status', $request->input('apply_status'));
        }
        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->input('keyword'));
            $query->where(function ($inner) use ($keyword) {
                $inner->where('phone', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        $list = $query->paginate(20);
        $viaSuper = (bool) $request->attributes->get('via_super');

        return [
            'list' => collect($list->items())->map(fn (User $user) => $this->presentUser($user, $viaSuper))->values(),
            'pagination' => [
                'current_page' => $list->currentPage(),
                'per_page' => $list->perPage(),
                'total' => $list->total(),
                'last_page' => $list->lastPage(),
            ],
            'pending_count' => User::query()->where('staff_apply_status', User::APPLY_PENDING)->count(),
            'park_roles' => $this->roleOptions(),
            'menus' => $this->menuOptions(),
            'can_configure' => true,
        ];
    }

    /**
     * @return array{user: array<string, mixed>, message: string}
     */
    public function updateUser(Request $request, int $id): array
    {
        $target = $this->findUser($id);
        $actor = $this->actor($request);
        $viaSuper = (bool) $request->attributes->get('via_super');
        $minLen = (int) config('auth_security.password.min_length', 8);

        $validated = $request->validate([
            'name' => 'nullable|string|max:50',
            'role' => 'nullable|string|in:visitor,merchant,park',
            'park_role' => 'nullable|string|max:32',
            'can_escalate' => 'nullable|boolean',
            'password' => 'nullable|string|min:'.$minLen,
        ], [
            'password.min' => "密码至少 {$minLen} 位",
        ]);

        $before = $this->snapshot($target);
        $role = $validated['role'] ?? $target->role;
        $parkRole = $target->park_role;

        if ($role === User::ROLE_PARK) {
            $incoming = $validated['park_role'] ?? $target->park_role;
            if (! is_string($incoming) || $incoming === '' || ! isset(ParkPermissions::roleMap()[$incoming])) {
                throw new StaffRuleException('请选择角色');
            }
            $parkRole = $incoming;
        } else {
            $parkRole = null;
        }

        $this->guardLastAdmin($target, $role, $parkRole, $viaSuper);
        $this->guardSelf($actor, $target, $role, $parkRole, $viaSuper);

        if (array_key_exists('name', $validated) && $validated['name'] !== null && $validated['name'] !== '') {
            $target->name = $validated['name'];
        }
        $target->role = $role;
        $target->park_role = $parkRole;

        if ($role === User::ROLE_PARK) {
            if ($target->staff_apply_status === User::APPLY_PENDING || $before['role'] !== User::ROLE_PARK) {
                $target->staff_apply_status = User::APPLY_APPROVED;
                $target->staff_reviewed_at = now();
                $target->staff_reject_reason = null;
            }
        } elseif ($before['role'] === User::ROLE_PARK) {
            $target->staff_apply_status = User::APPLY_NONE;
            $target->staff_reject_reason = null;
        }

        if ($viaSuper && array_key_exists('can_escalate', $validated) && $validated['can_escalate'] !== null) {
            $target->can_escalate = (bool) $validated['can_escalate'];
        }

        $passwordChanged = false;
        if (! empty($validated['password'])) {
            $target->password = $validated['password'];
            $passwordChanged = true;
        }

        if ($role === User::ROLE_PARK && ! $target->password) {
            throw new StaffRuleException('该用户还没有工作台密码，请先设置密码');
        }

        $target->save();
        $revoked = $this->revokeIfNeeded($target, $before, $passwordChanged);
        $this->audit($request, $actor, 'staff.user.update', $target, [
            'title' => '修改用户 #'.$target->id,
            'before' => $before,
            'after' => $this->snapshot($target),
            'password_changed' => $passwordChanged,
        ]);

        return [
            'user' => $this->presentUser($target->fresh(), $viaSuper),
            'message' => $revoked ? '已更新，该用户需重新登录工作台' : '已更新',
        ];
    }

    /**
     * @return array{user: array<string, mixed>, message: string}
     */
    public function reviewStaff(Request $request, int $id): array
    {
        $target = $this->findUser($id);
        $actor = $this->actor($request);
        $viaSuper = (bool) $request->attributes->get('via_super');
        $minLen = (int) config('auth_security.password.min_length', 8);

        $validated = $request->validate([
            'action' => 'required|string|in:approve,reject',
            'park_role' => 'nullable|string|max:32',
            'password' => 'nullable|string|min:'.$minLen,
            'reject_reason' => 'nullable|string|max:200',
        ], [
            'password.min' => "密码至少 {$minLen} 位",
            'action.required' => '请选择通过或拒绝',
        ]);

        if ($validated['action'] === 'reject') {
            $reason = trim((string) ($validated['reject_reason'] ?? ''));
            if ($reason === '') {
                throw new StaffRuleException('请填写拒绝原因');
            }
            $target->staff_apply_status = User::APPLY_REJECTED;
            $target->staff_reject_reason = $reason;
            $target->staff_reviewed_at = now();
            $target->save();
            $this->audit($request, $actor, 'staff.apply.reject', $target, [
                'title' => '拒绝工作人员申请 #'.$target->id,
                'reason' => $reason,
            ]);

            return [
                'user' => $this->presentUser($target->fresh(), $viaSuper),
                'message' => '已拒绝申请',
            ];
        }

        $parkRole = (string) ($validated['park_role'] ?? '');
        if ($parkRole === '' || ! isset(ParkPermissions::roleMap()[$parkRole])) {
            throw new StaffRuleException('请选择角色');
        }

        $this->guardLastAdmin($target, User::ROLE_PARK, $parkRole, $viaSuper);

        $before = $this->snapshot($target);
        $target->role = User::ROLE_PARK;
        $target->park_role = $parkRole;
        $target->staff_apply_status = User::APPLY_APPROVED;
        $target->staff_reviewed_at = now();
        $target->staff_reject_reason = null;

        $passwordChanged = false;
        if (! empty($validated['password'])) {
            $target->password = $validated['password'];
            $passwordChanged = true;
        }
        if (! $target->password) {
            throw new StaffRuleException('请设置工作台初始密码');
        }

        $target->save();
        $this->revokeIfNeeded($target, $before, $passwordChanged);
        $this->audit($request, $actor, 'staff.apply.approve', $target, [
            'title' => '通过工作人员申请 #'.$target->id,
            'park_role' => $parkRole,
            'password_set' => $passwordChanged,
        ]);

        return [
            'user' => $this->presentUser($target->fresh(), $viaSuper),
            'message' => '已通过，对方可用手机号和密码进入工作台',
        ];
    }

    public function listRoles(): array
    {
        $this->ensureReady();
        $counts = User::query()
            ->select('park_role', DB::raw('COUNT(*) as total'))
            ->where('role', User::ROLE_PARK)
            ->groupBy('park_role')
            ->pluck('total', 'park_role');

        $roles = ParkRole::query()->orderBy('sort')->orderBy('id')->get()->map(function (ParkRole $role) use ($counts) {
            $perms = is_array($role->permissions) ? $role->permissions : [];
            $menus = ParkPermissions::menusFromPermissions($perms);
            $labels = ParkPermissions::menuCatalog();

            return [
                'key' => $role->key,
                'name' => $role->name,
                'is_system' => (bool) $role->is_system,
                'menus' => $menus,
                'menu_text' => implode('、', array_map(fn ($key) => $labels[$key] ?? $key, $menus)),
                'user_count' => (int) ($counts[$role->key] ?? 0),
            ];
        })->values();

        return [
            'roles' => $roles,
            'menus' => $this->menuOptions(),
        ];
    }

    /**
     * @return array{role: array<string, mixed>, message: string}
     */
    public function createRole(Request $request): array
    {
        $this->ensureReady();
        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|max:32',
        ], [
            'name.required' => '请填写角色名称',
        ]);

        $name = trim($validated['name']);
        if ($name === '') {
            throw new StaffRuleException('请填写角色名称');
        }
        if (ParkRole::query()->where('name', $name)->exists()) {
            throw new StaffRuleException('已有同名角色');
        }

        $key = $this->newRoleKey();
        $perms = ParkPermissions::expandStoredPermissions($validated['permissions'] ?? [], $key);
        $sort = (int) ParkRole::query()->max('sort') + 1;
        $role = ParkRole::query()->create([
            'key' => $key,
            'name' => $name,
            'permissions' => $perms,
            'is_system' => false,
            'sort' => $sort,
        ]);
        ParkPermissions::flush();
        $this->audit($request, $this->actor($request), 'staff.role.create', null, [
            'title' => '新增角色 '.$name,
            'key' => $key,
            'permissions' => $perms,
        ], 'park_role', $key);

        return [
            'role' => ['key' => $role->key, 'name' => $role->name],
            'message' => '已新增角色',
        ];
    }

    /**
     * @return array{message: string}
     */
    public function updateRole(Request $request, string $key): array
    {
        $this->ensureReady();
        $role = ParkRole::query()->where('key', $key)->first();
        if (! $role) {
            throw new StaffRuleException('角色不存在', 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'permissions' => 'present|array',
            'permissions.*' => 'string|max:32',
        ], [
            'name.required' => '请填写角色名称',
        ]);

        $name = trim($validated['name']);
        if ($name === '') {
            throw new StaffRuleException('请填写角色名称');
        }
        if (ParkRole::query()->where('name', $name)->where('key', '!=', $key)->exists()) {
            throw new StaffRuleException('已有同名角色');
        }

        $before = ['name' => $role->name, 'permissions' => $role->permissions];
        $role->name = $name;
        $role->permissions = ParkPermissions::expandStoredPermissions($validated['permissions'] ?? [], $key);
        $role->save();
        ParkPermissions::flush();

        if ($before['permissions'] != $role->permissions) {
            User::query()->where('role', User::ROLE_PARK)->where('park_role', $key)->each(function (User $user) {
                $user->tokens()->delete();
            });
        }

        $this->audit($request, $this->actor($request), 'staff.role.update', null, [
            'title' => '修改角色 '.$name,
            'before' => $before,
            'after' => ['name' => $role->name, 'permissions' => $role->permissions],
        ], 'park_role', $key);

        return ['message' => '角色权限已保存，相关人员需重新登录工作台'];
    }

    public function deleteRole(Request $request, string $key): array
    {
        $this->ensureReady();
        $role = ParkRole::query()->where('key', $key)->first();
        if (! $role) {
            throw new StaffRuleException('角色不存在', 404);
        }
        if ($role->is_system || $role->key === ParkPermissions::ROLE_ADMIN) {
            throw new StaffRuleException('系统角色不能删除');
        }
        $used = User::query()->where('role', User::ROLE_PARK)->where('park_role', $key)->count();
        if ($used > 0) {
            throw new StaffRuleException('仍有用户使用该角色，请先调整他们的角色');
        }

        $role->delete();
        ParkPermissions::flush();
        $this->audit($request, $this->actor($request), 'staff.role.delete', null, [
            'title' => '删除角色 '.$role->name,
            'key' => $key,
        ], 'park_role', $key);

        return ['message' => '已删除角色'];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentUser(User $user, bool $includeEscalate): array
    {
        $parkRole = $user->isPark() ? ($user->park_role ?: null) : null;
        if ($parkRole !== null && ! isset(ParkPermissions::roleMap()[$parkRole])) {
            $parkRole = ParkPermissions::ROLE_ADMIN;
        }
        $labels = ParkPermissions::roleLabels();
        $status = $user->staff_apply_status ?: User::APPLY_NONE;

        $data = [
            'id' => $user->id,
            'phone' => $user->phone,
            'name' => $user->name,
            'role' => $user->role,
            'role_text' => $this->roleText($user->role),
            'park_role' => $parkRole,
            'park_role_text' => $parkRole ? ($labels[$parkRole] ?? $parkRole) : null,
            'phone_verified' => $user->phone_verified_at !== null,
            'has_password' => (bool) $user->password,
            'checked_in_at' => $user->checked_in_at,
            'merchant_apply_status' => $user->merchant_apply_status ?: User::APPLY_NONE,
            'merchant_applied_at' => $user->merchant_applied_at,
            'staff_apply_status' => $status,
            'staff_apply_status_text' => $this->staffStatusText($status),
            'staff_apply_note' => $user->staff_apply_note,
            'staff_reject_reason' => $user->staff_reject_reason,
            'staff_applied_at' => $user->staff_applied_at,
            'created_at' => $user->created_at,
        ];

        if ($includeEscalate) {
            $data['can_escalate'] = $user->canEscalate();
        }

        return $data;
    }

    private function ensureReady(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('park_roles')
            || ! \Illuminate\Support\Facades\Schema::hasColumn('xcx_users', 'staff_apply_status')) {
            throw new StaffRuleException('数据库还未更新。请在服务器执行 php artisan migrate --force，然后执行 php artisan route:clear', 503);
        }
    }

    private function findUser(int $id): User
    {
        $this->ensureReady();
        $user = User::query()->find($id);
        if (! $user) {
            throw new StaffRuleException('用户不存在', 404);
        }

        return $user;
    }

    private function actor(Request $request): ?User
    {
        $user = $request->attributes->get('park_user');

        return $user instanceof User ? $user : null;
    }

    /**
     * @return array{role: string, park_role: ?string, can_escalate: bool, name: ?string}
     */
    private function snapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'role' => $user->role,
            'park_role' => $user->park_role,
            'can_escalate' => (bool) $user->can_escalate,
        ];
    }

    private function guardLastAdmin(User $target, string $role, ?string $parkRole, bool $viaSuper): void
    {
        if ($viaSuper) {
            return;
        }
        $wasAdmin = $target->role === User::ROLE_PARK
            && ParkPermissions::normalizeRole($target->park_role) === ParkPermissions::ROLE_ADMIN;
        $willBeAdmin = $role === User::ROLE_PARK && $parkRole === ParkPermissions::ROLE_ADMIN;
        if (! $wasAdmin || $willBeAdmin) {
            return;
        }
        $others = User::query()
            ->where('role', User::ROLE_PARK)
            ->where('park_role', ParkPermissions::ROLE_ADMIN)
            ->where('id', '!=', $target->id)
            ->count();
        if ($others === 0) {
            throw new StaffRuleException('至少保留一名管理员');
        }
    }

    private function guardSelf(?User $actor, User $target, string $role, ?string $parkRole, bool $viaSuper): void
    {
        if ($viaSuper || ! $actor || $actor->id !== $target->id) {
            return;
        }
        if ($role !== User::ROLE_PARK || $parkRole !== ParkPermissions::ROLE_ADMIN) {
            throw new StaffRuleException('不能取消自己的管理员身份');
        }
    }

    /**
     * @param  array{role: string, park_role: ?string}  $before
     */
    private function revokeIfNeeded(User $target, array $before, bool $passwordChanged): bool
    {
        $changed = $passwordChanged
            || $before['role'] !== $target->role
            || ($before['park_role'] ?? null) !== ($target->park_role ?? null);
        if (! $changed) {
            return false;
        }
        $target->tokens()->delete();
        $target->superTokens()->delete();

        return true;
    }

    private function audit(Request $request, ?User $actor, string $action, ?User $target, array $payload, string $type = 'user', ?string $targetId = null): void
    {
        if ($request->attributes->get('via_super')) {
            return;
        }
        $this->auditLogger->log(
            $actor,
            $action,
            $type,
            $targetId ?? ($target ? (string) $target->id : null),
            $payload,
            $request
        );
    }

    private function newRoleKey(): string
    {
        do {
            $key = 'r'.substr(bin2hex(random_bytes(4)), 0, 8);
        } while (ParkRole::query()->where('key', $key)->exists());

        return $key;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return collect(ParkPermissions::roleLabels())
            ->map(fn ($label, $key) => ['value' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function menuOptions(): array
    {
        return collect(ParkPermissions::menuCatalog())
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
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
