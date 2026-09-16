<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AdminAuditLog::query()->orderByDesc('id');

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->input('action').'%');
        }
        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%'.$request->input('phone').'%');
        }

        $logs = $query->paginate(30)->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'filters' => [
                'action' => $request->input('action', ''),
                'phone' => $request->input('phone', ''),
            ],
        ]);
    }
}
