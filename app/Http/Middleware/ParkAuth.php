<?php

namespace App\Http\Middleware;

use App\Services\AuthTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ParkAuth
{
    public const ACT_AS_HEADER = 'X-Park-Act-As';

    public function __construct(
        private readonly AuthTokenService $authTokens,
    ) {}

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

        // 1) 园区人员 Token：正常工作台会话
        $userToken = $this->authTokens->findUserToken($token);

        if ($userToken && $userToken->user && $userToken->user->isPark()) {
            $request->attributes->set('park_user', $userToken->user);
            $request->attributes->set('via_super', false);
            $request->attributes->set('park_act_as', false);

            return $next($request);
        }

        // 2) 超管 Token：必须显式代管头，禁止静默冒充园区会话
        $superToken = $this->authTokens->findSuperToken($token);

        if ($superToken && $superToken->user && $superToken->user->canEscalate()) {
            if (! $this->wantsActAsPark($request)) {
                return response()->json([
                    'code' => 403,
                    'msg' => '超管访问园区接口须开启代管（X-Park-Act-As）',
                    'data' => null,
                ], 403);
            }

            $request->attributes->set('park_user', $superToken->user);
            $request->attributes->set('super_user', $superToken->user);
            $request->attributes->set('via_super', true);
            $request->attributes->set('park_act_as', true);

            return $next($request);
        }

        return response()->json([
            'code' => 401,
            'msg' => '无园区管理权限',
            'data' => null,
        ], 401);
    }

    private function wantsActAsPark(Request $request): bool
    {
        $raw = strtolower(trim((string) $request->header(self::ACT_AS_HEADER, '')));

        return in_array($raw, ['1', 'true', 'yes', 'on'], true);
    }
}
