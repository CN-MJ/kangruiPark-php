<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enterprise;
use App\Models\User;
use App\Support\ParkPermissions;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users_total' => User::count(),
                'park_staff' => User::query()->where('role', User::ROLE_PARK)->count(),
                'enterprises' => Enterprise::query()->count(),
                'roles' => ParkPermissions::roleLabels(),
            ],
        ]);
    }
}
