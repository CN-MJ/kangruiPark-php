<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\WeChatOpenidBinder;
use App\Support\WeChatBindState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeChatBindController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly WeChatOpenidBinder $binder,
    ) {}

    /**
     * 兼容旧 H5：仍可用 code+state 绑定（主流程已改为服务端渲染绑定）。
     */
    public function bindOpenid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:128',
            'state' => 'nullable|string|max:256',
            'type' => 'nullable|string|max:32',
            'phone' => 'nullable|string|max:20',
            'merchant_id' => 'nullable|integer|min:1',
            'user_id' => 'nullable|integer|min:1',
        ], [
            'code.required' => '缺少授权 code',
        ]);

        $state = (string) ($validated['state'] ?? '');
        if ($state === '') {
            $type = (string) ($validated['type'] ?? '');
            if ($type === 'user' || $type === WeChatBindState::TYPE_VISITOR) {
                $state = WeChatBindState::forVisitor((string) ($validated['phone'] ?? ''));
            } elseif ($type === WeChatBindState::TYPE_MERCHANT) {
                $state = WeChatBindState::forMerchant((int) ($validated['merchant_id'] ?? 0));
            }
        }

        $result = $this->binder->bindByCode(
            $validated['code'],
            $state,
            (int) ($validated['user_id'] ?? 0)
        );

        if (! $result['ok']) {
            return $this->error($result['msg'], 400);
        }

        return $this->success([
            'type' => $result['type'] ?? null,
            'user_id' => $result['user_id'] ?? null,
            'merchant_id' => $result['merchant_id'] ?? null,
            'phone' => $result['phone'] ?? null,
            'is_subscribed' => (bool) ($result['is_subscribed'] ?? false),
        ], $result['msg']);
    }

    public function checkSubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state' => 'nullable|string|max:256',
            'type' => 'nullable|string|max:32',
            'phone' => 'nullable|string|max:20',
            'merchant_id' => 'nullable|integer|min:1',
        ]);

        $state = (string) ($validated['state'] ?? '');
        if ($state === '') {
            $type = (string) ($validated['type'] ?? '');
            if ($type === 'user' || $type === WeChatBindState::TYPE_VISITOR) {
                $state = WeChatBindState::forVisitor((string) ($validated['phone'] ?? ''));
            } elseif ($type === WeChatBindState::TYPE_MERCHANT) {
                $state = WeChatBindState::forMerchant((int) ($validated['merchant_id'] ?? 0));
            }
        }

        $result = $this->binder->checkSubscribeByState($state);
        if (! $result['ok']) {
            return $this->error($result['msg'], 400);
        }

        return $this->success([
            'is_subscribed' => (bool) ($result['is_subscribed'] ?? false),
        ]);
    }
}
