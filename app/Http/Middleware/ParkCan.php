<?php

namespace App\Http\Middleware;

use App\Support\ParkPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 用法：middleware('park.can:enterprises') 或 park.can:categories,categories_read（OR）
 */
class ParkCan
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        if ($request->attributes->get('via_super')) {
            return $next($request);
        }

        $user = $request->attributes->get('park_user');
        if (! $user) {
            return response()->json([
                'code' => 401,
                'msg' => '请先登录园区工作台',
                'data' => null,
            ], 401);
        }

        $needed = [];
        foreach ($abilities as $ability) {
            foreach (explode(',', $ability) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $needed[] = $part;
                }
            }
        }

        if ($needed === []) {
            return $next($request);
        }

        if (! ParkPermissions::allowsAny($user, $needed)) {
            return response()->json([
                'code' => 403,
                'msg' => '当前账号无权操作此功能',
                'data' => [
                    'required' => $needed,
                    'park_role' => ParkPermissions::normalizeRole($user->park_role ?? null),
                ],
            ], 403);
        }

        return $next($request);
    }
}
