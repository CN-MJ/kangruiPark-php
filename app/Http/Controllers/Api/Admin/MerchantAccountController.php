<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Enterprise;
use App\Models\Merchant;
use App\Models\User;
use App\Services\MerchantEnterpriseBinder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 园区：维护企业工作台账号（merchants）与企业（enterprises）的绑定。
 */
class MerchantAccountController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MerchantEnterpriseBinder $binder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 20), 1), 50);

        $query = Merchant::query()
            ->with('enterprise:id,name')
            ->orderByDesc('id');

        if ($request->filled('enterprise_id')) {
            $query->where('enterprise_id', (int) $request->input('enterprise_id'));
        }
        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->input('keyword'));
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('contact_phone', 'like', "%{$keyword}%")
                        ->orWhere('contact_name', 'like', "%{$keyword}%");
                });
            }
        }

        $page = $query->paginate($perPage);

        return $this->success([
            'list' => collect($page->items())->map(fn (Merchant $m) => $this->format($m))->values(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    /**
     * 创建或更新商户账号，并绑定到企业。
     */
    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enterprise_id' => 'required|integer|exists:enterprises,id',
            'contact_phone' => 'required|string|max:20',
            'contact_name' => 'nullable|string|max:50',
            'name' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:6|max:64',
            'status' => ['sometimes', Rule::in([Merchant::STATUS_ACTIVE, Merchant::STATUS_INACTIVE])],
        ], [
            'enterprise_id.required' => '请选择企业',
            'contact_phone.required' => '请填写登录手机号',
        ]);

        $enterprise = Enterprise::query()->find($validated['enterprise_id']);
        if (! $enterprise) {
            return $this->error('企业不存在', 404);
        }

        $merchant = Merchant::query()
            ->where('contact_phone', $validated['contact_phone'])
            ->first();

        $payload = [
            'enterprise_id' => $enterprise->id,
            'name' => $validated['name'] ?? $enterprise->name,
            'contact_name' => $validated['contact_name'] ?? $enterprise->contact_name,
            'contact_phone' => $validated['contact_phone'],
            'status' => $validated['status'] ?? Merchant::STATUS_ACTIVE,
        ];

        if (! empty($validated['password'])) {
            // Model 已有 hashed cast，传入明文即可
            $payload['password'] = $validated['password'];
        } elseif (! $merchant || ! $merchant->password) {
            // 首次创建且未设密码：默认手机号后 6 位（不足则补 000000）
            $phone = preg_replace('/\D+/', '', $validated['contact_phone']) ?: '000000';
            $payload['password'] = substr($phone, -6) ?: '000000';
        }

        if ($merchant) {
            $merchant->update($payload);
        } else {
            $merchant = Merchant::create($payload);
        }

        // 若该手机号已是小程序用户，同步为商户并绑定企业
        $xcxUser = User::query()->where('phone', $validated['contact_phone'])->first();
        if ($xcxUser && $xcxUser->role !== User::ROLE_PARK) {
            $this->binder->bind($xcxUser, $enterprise);
        }

        return $this->success($this->format($merchant->fresh()->load('enterprise:id,name')), '企业工作台账号已就绪');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $merchant = Merchant::query()->find($id);
        if (! $merchant) {
            return $this->error('账号不存在', 404);
        }

        $validated = $request->validate([
            'enterprise_id' => 'nullable|integer|exists:enterprises,id',
            'contact_name' => 'nullable|string|max:50',
            'name' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:6|max:64',
            'status' => ['sometimes', Rule::in([Merchant::STATUS_ACTIVE, Merchant::STATUS_INACTIVE])],
        ]);

        if (array_key_exists('enterprise_id', $validated)) {
            $merchant->enterprise_id = $validated['enterprise_id'];
        }
        if (array_key_exists('contact_name', $validated)) {
            $merchant->contact_name = $validated['contact_name'];
        }
        if (array_key_exists('name', $validated)) {
            $merchant->name = $validated['name'];
        }
        if (array_key_exists('status', $validated)) {
            $merchant->status = $validated['status'];
        }
        if (! empty($validated['password'])) {
            $merchant->password = $validated['password'];
        }
        $merchant->save();

        return $this->success($this->format($merchant->fresh()->load('enterprise:id,name')), '已保存');
    }

    private function format(Merchant $m): array
    {
        return [
            'id' => $m->id,
            'name' => $m->name,
            'contact_name' => $m->contact_name,
            'contact_phone' => $m->contact_phone,
            'status' => (int) $m->status,
            'enterprise_id' => $m->enterprise_id,
            'enterprise' => $m->enterprise ? [
                'id' => $m->enterprise->id,
                'name' => $m->enterprise->name,
            ] : null,
            'has_mp_openid' => is_string($m->mp_openid) && $m->mp_openid !== '',
            'created_at' => optional($m->created_at)?->toDateTimeString(),
        ];
    }
}
