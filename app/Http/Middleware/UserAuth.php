<?php

namespace App\Http\Middleware;

use App\Models\UserToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'code' => 401,
                'msg' => '请先登录',
                'data' => null,
            ], 401);
        }

        $userToken = UserToken::query()
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->with('user')
            ->first();

        if (! $userToken || ! $userToken->user) {
            return response()->json([
                'code' => 401,
                'msg' => '登录已过期，请重新登录',
                'data' => null,
            ], 401);
        }

        if ($userToken->user->phone_verified_at === null) {
            return response()->json([
                'code' => 403,
                'msg' => '请先微信授权手机号登录',
                'data' => null,
            ], 403);
        }

        $request->attributes->set('xcx_user', $userToken->user);

        return $next($request);
    }
}
