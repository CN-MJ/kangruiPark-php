<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AdminAuditLogger
{
    public function log(
        ?User $user,
        string $action,
        ?string $targetType = null,
        ?string $targetId = null,
        ?array $payload = null,
        ?Request $request = null,
    ): void {
        AdminAuditLog::create([
            'user_id' => $user?->id,
            'phone' => $user?->phone,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId !== null ? (string) $targetId : null,
            'payload' => $payload,
            'ip' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 255) ?: null,
        ]);
    }
}
