<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->whereIn('role', [User::ROLE_VISITOR, User::ROLE_MERCHANT])
            ->orderByRaw("CASE merchant_apply_status WHEN 'pending' THEN 0 WHEN 'rejected' THEN 1 WHEN 'approved' THEN 2 ELSE 3 END")
            ->orderByDesc('merchant_applied_at')
            ->orderByDesc('updated_at');

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('apply_status')) {
            $query->where('merchant_apply_status', $request->input('apply_status'));
        }

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('phone', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        $list = $query->paginate(20);

        $items = collect($list->items())->map(fn (User $user) => $this->formatUser($user));

        return $this->success([
            'list' => $items,
            'pagination' => [
                'current_page' => $list->currentPage(),
                'per_page' => $list->perPage(),
                'total' => $list->total(),
                'last_page' => $list->lastPage(),
            ],
            'pending_count' => User::query()
                ->where('merchant_apply_status', User::APPLY_PENDING)
                ->count(),
        ]);
    }

    /**
     * 旧入驻审核已停用
     */
    public function approveMerchant(int $id): JsonResponse
    {
        return $this->error('入驻审核已停用，请使用工作台「企业库」管理企业', 410);
    }

    /**
     * 旧入驻审核已停用
     */
    public function rejectMerchant(int $id): JsonResponse
    {
        return $this->error('入驻审核已停用，请使用工作台「企业库」管理企业', 410);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'phone' => $user->phone,
            'name' => $user->name,
            'role' => $user->role,
            'role_text' => $this->roleText($user->role),
            'merchant_apply_status' => $user->merchant_apply_status ?: User::APPLY_NONE,
            'merchant_apply_status_text' => $this->applyStatusText($user->merchant_apply_status ?: User::APPLY_NONE),
            'checked_in_at' => $user->checked_in_at,
            'merchant_applied_at' => $user->merchant_applied_at,
            'created_at' => $user->created_at,
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

    private function applyStatusText(string $status): string
    {
        return match ($status) {
            User::APPLY_PENDING => '待审核',
            User::APPLY_APPROVED => '已通过',
            User::APPLY_REJECTED => '已拒绝',
            default => '未申请',
        };
    }
}
