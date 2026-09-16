<?php

namespace App\Support;

use App\Models\User;

/**
 * 园区工作台轻量 RBAC：角色 → 权限点
 */
class ParkPermissions
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_OPS = 'ops';

    public const ROLE_ENTERPRISE = 'enterprise';

    public const ROLE_CATEGORY = 'category';

    public const ROLE_PARKING = 'parking';

    public const ROLE_INVEST = 'invest';

    /** @return array<string, list<string>> */
    public static function roleMap(): array
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
    public static function roleLabels(): array
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
}
