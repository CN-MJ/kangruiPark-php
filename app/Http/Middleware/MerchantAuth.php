<?php

namespace App\Http\Middleware;

use App\Services\AuthTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MerchantAuth
{
    public function __construct(
        private readonly AuthTokenService $authTokens,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('auth_security.merchant.workbench_enabled', false)) {
            return response()->json([
                'code' => 410,
                'msg' => '商户工作台已停用',
                'data' => null,
            ], 410);
        }

        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'code' => 401,
                'msg' => '请先登录商户工作台',
                'data' => null,
            ], 401);
        }

        $merchantToken = $this->authTokens->findMerchantToken($token);

        if (! $merchantToken || ! $merchantToken->merchant || (int) $merchantToken->merchant->status !== 1) {
            return response()->json([
                'code' => 401,
                'msg' => '无商户权限',
                'data' => null,
            ], 401);
        }

        $request->attributes->set('merchant_user', $merchantToken->merchant);

        return $next($request);
    }
}
