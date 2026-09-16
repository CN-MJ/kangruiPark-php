<?php

namespace App\Http\Middleware;

use App\Support\ParkPermissions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureParkWebAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user || ! $user->isPark()) {
            Auth::guard('web')->logout();

            return redirect()->route('admin.login')
                ->withErrors(['phone' => '请使用园区管理员账号登录']);
        }

        $role = ParkPermissions::normalizeRole($user->park_role ?? null);
        if ($role !== ParkPermissions::ROLE_ADMIN) {
            Auth::guard('web')->logout();

            return redirect()->route('admin.login')
                ->withErrors(['phone' => '仅园区管理员（park_role=admin）可进入 Web 管理端']);
        }

        return $next($request);
    }
}
