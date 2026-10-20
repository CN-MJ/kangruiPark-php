<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Support\ParkPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        $query = User::query()
            ->where(function ($inner) {
                $inner->where('can_escalate', false)->orWhereNull('can_escalate');
            })
            ->orderByDesc('id');

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }
        if ($request->filled('keyword')) {
            $kw = trim((string) $request->input('keyword'));
            $query->where(function ($q) use ($kw) {
                $q->where('phone', 'like', "%{$kw}%")
                    ->orWhere('name', 'like', "%{$kw}%");
            });
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'parkRoles' => ParkPermissions::roleLabels(),
            'filters' => [
                'keyword' => $request->input('keyword', ''),
                'role' => $request->input('role', ''),
            ],
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var User $actor */
        $actor = Auth::guard('web')->user();

        $target = User::find($id);
        if (! $target || $target->canEscalate()) {
            return back()->withErrors(['form' => '用户不存在']);
        }

        $parkRoleKeys = implode(',', array_keys(ParkPermissions::roleMap()));
        $validated = $request->validate([
            'name' => 'nullable|string|max:50',
            'role' => 'required|string|in:visitor,merchant,park',
            'park_role' => 'nullable|string|in:'.$parkRoleKeys,
            'can_escalate' => 'nullable|boolean',
            'password' => 'nullable|string|min:'.((int) config('auth_security.password.min_length', 8)),
        ]);

        $before = [
            'name' => $target->name,
            'role' => $target->role,
            'park_role' => $target->park_role,
            'can_escalate' => (bool) $target->can_escalate,
        ];

        $target->name = $validated['name'] ?? $target->name;
        $target->role = $validated['role'];

        if ($target->role === User::ROLE_PARK) {
            $target->park_role = ParkPermissions::normalizeRole($validated['park_role'] ?? null);
        } else {
            $target->park_role = null;
        }

        $passwordChanged = false;
        if (! empty($validated['password'])) {
            $target->password = $validated['password'];
            $passwordChanged = true;
        }

        $target->save();

        $roleOrPermChanged = ($before['role'] !== $target->role)
            || (($before['park_role'] ?? null) !== ($target->park_role ?? null));
        if ($roleOrPermChanged || $passwordChanged) {
            $target->tokens()->delete();
            $target->superTokens()->delete();
        }

        $after = [
            'name' => $target->name,
            'role' => $target->role,
            'park_role' => $target->park_role,
            'can_escalate' => (bool) $target->can_escalate,
        ];

        $this->auditLogger->log($actor, 'web.user.update', 'user', (string) $target->id, [
            'channel' => 'web_admin',
            'before' => $before,
            'after' => $after,
            'password_changed' => $passwordChanged,
        ], $request);

        $msg = '已更新';
        if ($roleOrPermChanged || $passwordChanged) {
            $msg .= '（对方需重新登录小程序工作台）';
        }

        return back()->with('status', $msg);
    }
}
