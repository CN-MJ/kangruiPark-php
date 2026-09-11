<?php

namespace App\Http\Middleware;

use App\Models\SuperToken;
use App\Models\User;
use App\Models\UserToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ParkAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'code' => 401,
                'msg' => '请先登录园区工作台',
                'data' => null,
            ], 401);
        }

        $userToken = UserToken::query()
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->with('user')
            ->first();

        if ($userToken && $userToken->user && $userToken->user->isPark()) {
            $request->attributes->set('park_user', $userToken->user);
            $request->attributes->set('via_super', false);

            return $next($request);
        }

        // 超管升权 Token：可访问全部园区管理接口
        $superToken = SuperToken::query()
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->with('user')
            ->first();

        if ($superToken && $superToken->user && $superToken->user->canEscalate()) {
            $request->attributes->set('park_user', $superToken->user);
            $request->attributes->set('super_user', $superToken->user);
            $request->attributes->set('via_super', true);

            return $next($request);
        }

        return response()->json([
            'code' => 401,
            'msg' => '无园区管理权限',
            'data' => null,
        ], 401);
    }
}
