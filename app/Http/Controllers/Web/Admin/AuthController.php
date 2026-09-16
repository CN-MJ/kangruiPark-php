<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Support\ParkPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('admin.users.index');
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'password' => 'required|string',
        ], [
            'phone.required' => '请输入手机号',
            'password.required' => '请输入密码',
        ]);

        $user = User::query()
            ->where('phone', $validated['phone'])
            ->where('role', User::ROLE_PARK)
            ->first();

        if (! $user || ! $user->password || ! Hash::check($validated['password'], $user->password)) {
            return back()->withInput($request->only('phone'))
                ->withErrors(['phone' => '手机号或密码错误']);
        }

        $parkRole = ParkPermissions::normalizeRole($user->park_role ?? null);
        if ($parkRole !== ParkPermissions::ROLE_ADMIN) {
            return back()->withInput($request->only('phone'))
                ->withErrors(['phone' => '仅园区管理员可登录 Web 管理端']);
        }

        Auth::guard('web')->login($user, false);
        $request->session()->regenerate();

        $this->auditLogger->log($user, 'web.admin.login', 'user', (string) $user->id, [
            'channel' => 'web_admin',
        ], $request);

        return redirect()->intended(route('admin.users.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();
        if ($user) {
            $this->auditLogger->log($user, 'web.admin.logout', 'user', (string) $user->id, [
                'channel' => 'web_admin',
            ], $request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
