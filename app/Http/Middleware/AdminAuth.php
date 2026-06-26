<?php

namespace App\Http\Middleware;

use App\Models\AdminToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'code' => 401,
                'msg' => '请先登录管理后台',
                'data' => null,
            ], 401);
        }

        $adminToken = AdminToken::query()
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->with('admin')
            ->first();

        if (! $adminToken || ! $adminToken->admin) {
            return response()->json([
                'code' => 401,
                'msg' => '登录已过期，请重新登录',
                'data' => null,
            ], 401);
        }

        $request->attributes->set('admin', $adminToken->admin);

        return $next($request);
    }
}
