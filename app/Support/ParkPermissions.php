<?php

namespace App\Support;

use App\Models\ParkRole;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * 园区工作台轻量 RBAC：岗位 → 可访问的工作台菜单。
 * 岗位数据在 park_roles；库未就绪时回退到内置岗位，避免迁移前工作台不可用。
 */
class ParkPermissions
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_OPS = 'ops';

    public const ROLE_ENTERPRISE = 'enterprise';

    public const ROLE_CATEGORY = 'category';

    public const ROLE_PARKING = 'parking';

    public const ROLE_INVEST = 'invest';

    public const CACHE_KEY = 'park_permissions.catalog';

    /** @return array<string, list<string>> */
    public static function defaultRoleMap(): array
    {
        return [
            self::ROLE_ADMIN => ['*'],
            self::ROLE_OPS => [
                'dashboard',
                'appointments',
                'news',
                'policies',
                'activities',
                'service_teams',
                'upload',
            ],
            self::ROLE_ENTERPRISE => [
                'dashboard',
                'enterprises',
                'categories_read',
                'upload',
            ],
            self::ROLE_CATEGORY => [
                'dashboard',
                'categories',
                'categories_read',
                'enterprises',
                'upload',
            ],
            self::ROLE_PARKING => [
                'dashboard',
                'parking',
            ],
            self::ROLE_INVEST => [
                'dashboard',
                'houses',
                'investments',
            ],
        ];
    }

    /** @return array<string, string> */
    public static function defaultRoleLabels(): array
    {
        return [
            self::ROLE_ADMIN => '管理员',
            self::ROLE_OPS => '综合运营',
            self::ROLE_ENTERPRISE => '健康企业汇录入',
            self::ROLE_CATEGORY => '分类管理员',
            self::ROLE_PARKING => '停车岗',
            self::ROLE_INVEST => '招商岗',
        ];
    }

    /**
     * 工作台菜单。勾选这些即表示该岗位可以进入对应菜单。
     *
     * @return array<string, string>
     */
    public static function menuCatalog(): array
    {
        return [
            'appointments' => '预约管理',
            'parking' => '停车管理',
            'news' => '新闻管理',
            'enterprises' => '健康企业汇',
            'categories' => '企业类型',
            'houses' => '房源管理',
            'policies' => '政策管理',
            'service_teams' => '服务团队',
            'activities' => '活动管理',
            'investments' => '招商意向',
            'users' => '用户管理',
            'roles' => '角色管理',
        ];
    }

    /**
     * @return list<array{key: string, name: string, permissions: list<string>, sort: int}>
     */
    public static function defaultRoleRows(): array
    {
        $labels = self::defaultRoleLabels();
        $rows = [];
        $sort = 1;
        foreach (self::defaultRoleMap() as $key => $permissions) {
            $rows[] = [
                'key' => $key,
                'name' => $labels[$key],
                'permissions' => $permissions,
                'sort' => $sort++,
            ];
        }

        return $rows;
    }

    /** @return array<string, list<string>> */
    public static function roleMap(): array
    {
        return self::catalog()['map'];
    }

    /** @return array<string, string> */
    public static function roleLabels(): array
    {
        return self::catalog()['labels'];
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function normalizeRole(?string $role): string
    {
        $role = trim((string) $role);
        if ($role !== '' && isset(self::roleMap()[$role])) {
            return $role;
        }

        return self::ROLE_ADMIN;
    }

    /**
     * @return list<string>
     */
    public static function permissionsForRole(?string $role): array
    {
        $key = self::normalizeRole($role);
        $perms = self::roleMap()[$key] ?? ['dashboard'];

        return array_values($perms);
    }

    public static function allows(User $user, string $permission): bool
    {
        if (! $user->isPark() && ! $user->canEscalate()) {
            return false;
        }

        $perms = self::permissionsForRole($user->park_role ?? null);
        if (in_array('*', $perms, true)) {
            return true;
        }

        return in_array($permission, $perms, true);
    }

    public static function allowsAny(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::allows($user, (string) $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 把界面勾选的菜单展开成实际权限点。
     * 管理员始终保留「用户管理」。全部菜单勾选时存为 *。
     *
     * @param  list<string>  $selected
     * @return list<string>
     */
    public static function expandStoredPermissions(array $selected, string $roleKey): array
    {
        $allowed = array_keys(self::menuCatalog());
        $menus = array_values(array_unique(array_intersect($selected, $allowed)));
        if ($roleKey === self::ROLE_ADMIN) {
            if (! in_array('users', $menus, true)) {
                $menus[] = 'users';
            }
            if (! in_array('roles', $menus, true)) {
                $menus[] = 'roles';
            }
        }

        if (count(array_intersect($allowed, $menus)) === count($allowed)) {
            return ['*'];
        }

        $perms = array_merge(['dashboard'], $menus);
        if (in_array('enterprises', $menus, true) || in_array('categories', $menus, true)) {
            $perms[] = 'upload';
            $perms[] = 'categories_read';
        }

        return array_values(array_unique($perms));
    }

    /**
     * @param  list<string>  $perms
     * @return list<string>
     */
    public static function menusFromPermissions(array $perms): array
    {
        $menus = array_keys(self::menuCatalog());
        if (in_array('*', $perms, true)) {
            return $menus;
        }

        return array_values(array_intersect($menus, $perms));
    }

    /**
     * @return array{map: array<string, list<string>>, labels: array<string, string>}
     */
    private static function catalog(): array
    {
        $fallback = [
            'map' => self::defaultRoleMap(),
            'labels' => self::defaultRoleLabels(),
        ];

        try {
            return Cache::remember(self::CACHE_KEY, 300, function () use ($fallback) {
                if (! Schema::hasTable('park_roles')) {
                    return $fallback;
                }

                $rows = ParkRole::query()->orderBy('sort')->orderBy('id')->get();
                if ($rows->isEmpty()) {
                    return $fallback;
                }

                $map = [];
                $labels = [];
                foreach ($rows as $row) {
                    $perms = is_array($row->permissions) ? array_values($row->permissions) : [];
                    if ($perms === []) {
                        $perms = ['dashboard'];
                    }
                    $map[$row->key] = $perms;
                    $labels[$row->key] = $row->name;
                }

                if (! isset($map[self::ROLE_ADMIN])) {
                    $map[self::ROLE_ADMIN] = ['*'];
                    $labels[self::ROLE_ADMIN] = $labels[self::ROLE_ADMIN] ?? '管理员';
                }

                return ['map' => $map, 'labels' => $labels];
            });
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
