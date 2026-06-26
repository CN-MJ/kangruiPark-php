<?php

namespace App\Http\Middleware;

use App\Models\MerchantToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MerchantAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'code' => 401,
                'msg' => '请先登录商户工作台',
                'data' => null,
            ], 401);
        }

        $merchantToken = MerchantToken::query()
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->with('merchant')
            ->first();

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
