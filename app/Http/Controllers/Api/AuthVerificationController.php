<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Sms\SmsVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 短信验证码接口（预留）
 * SMS_ENABLED=false 时，send 接口返回提示；verify 可用于后续绑手机流程
 */
class AuthVerificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SmsVerificationService $smsService,
    ) {}

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'purpose' => 'required|string|in:'.implode(',', SmsVerificationService::allowedPurposes()),
        ]);

        try {
            $this->smsService->send(
                $validated['phone'],
                $validated['purpose'],
                $request->ip(),
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }

        $msg = '验证码已发送';
        $data = null;
        if (! config('sms.enabled', false) && app()->environment('local')) {
            $msg = '验证码已生成（开发环境请查看后端日志，或使用 000000）';
            $data = ['dev_code_hint' => '000000'];
        }

        return $this->success($data, $msg);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'code' => 'required|string|max:10',
            'purpose' => 'required|string|in:'.implode(',', SmsVerificationService::allowedPurposes()),
        ]);

        $ok = config('sms.enabled', false)
            ? $this->smsService->verify($validated['phone'], $validated['code'], $validated['purpose'])
            : $this->smsService->verifyOrDevBypass($validated['phone'], $validated['code'], $validated['purpose']);

        if (! $ok) {
            return $this->error('验证码错误或已过期', 400);
        }

        return $this->success([
            'phone' => $validated['phone'],
            'purpose' => $validated['purpose'],
            'verified' => true,
        ], '验证成功');
    }
}
