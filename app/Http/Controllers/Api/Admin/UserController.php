<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\StaffDirectory;
use App\Services\StaffRuleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly StaffDirectory $staff,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success($this->staff->listUsers($request));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->respond(fn () => $this->staff->updateUser($request, $id));
    }

    public function reviewStaff(Request $request, int $id): JsonResponse
    {
        return $this->respond(fn () => $this->staff->reviewStaff($request, $id));
    }

    public function roles(): JsonResponse
    {
        return $this->success($this->staff->listRoles());
    }

    public function storeRole(Request $request): JsonResponse
    {
        return $this->respond(fn () => $this->staff->createRole($request));
    }

    public function updateRole(Request $request, string $key): JsonResponse
    {
        return $this->respond(fn () => $this->staff->updateRole($request, $key));
    }

    public function destroyRole(Request $request, string $key): JsonResponse
    {
        return $this->respond(fn () => $this->staff->deleteRole($request, $key));
    }

    /**
     * 旧入驻审核已停用
     */
    public function approveMerchant(int $id): JsonResponse
    {
        return $this->error('入驻审核已停用，请使用工作台「用户管理」处理工作人员申请', 410);
    }

    /**
     * 旧入驻审核已停用
     */
    public function rejectMerchant(int $id): JsonResponse
    {
        return $this->error('入驻审核已停用，请使用工作台「用户管理」处理工作人员申请', 410);
    }

    /**
     * @param  callable(): array{message?: string}  $action
     */
    private function respond(callable $action): JsonResponse
    {
        try {
            $result = $action();
        } catch (StaffRuleException $e) {
            return $this->error($e->getMessage(), $e->status);
        }

        $message = $result['message'] ?? '已更新';
        unset($result['message']);

        return $this->success($result === [] ? null : $result, $message);
    }
}
