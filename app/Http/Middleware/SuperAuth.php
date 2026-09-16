<?php

namespace App\Http\Middleware;

use App\Services\AuthTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAuth
{
    public function __construct(
        private readonly AuthTokenService $authTokens,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'code' => 401,
                'msg' => '请先完成超管验证',
                'data' => null,
            ], 401);
        }

        $superToken = $this->authTokens->findSuperToken($token);

        if (! $superToken || ! $superToken->user || ! $superToken->user->canEscalate()) {
            return response()->json([
                'code' => 401,
                'msg' => '超管会话已过期，请重新验证',
                'data' => null,
            ], 401);
        }

        $allowlist = trim((string) config('auth_security.super.ip_allowlist', ''));
        if ($allowlist !== '') {
            $allowed = array_filter(array_map('trim', explode(',', $allowlist)));
            if ($allowed && ! in_array($request->ip(), $allowed, true)) {
                return response()->json([
                    'code' => 403,
                    'msg' => '当前网络不允许进入控制台',
                    'data' => null,
                ], 403);
            }
        }

        $request->attributes->set('super_user', $superToken->user);
        $request->attributes->set('super_token', $token);

        return $next($request);
    }
}
