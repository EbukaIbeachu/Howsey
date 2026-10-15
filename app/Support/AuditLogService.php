<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    public function log(string $actionType, ?Model $record = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        $user = auth()->user();
        $request = app(Request::class);

        AuditLog::create([
            'user_id' => $user?->id,
            'role' => $user?->role?->name,
            'action_type' => $actionType,
            'record_id' => $record?->getKey(),
            'record_type' => $record ? class_basename($record) : null,
            'building_id' => $record?->building_id ?? optional(optional($record)->unit)->building_id,
            'company_id' => $record?->company_id ?? $user?->company_id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => str_contains(strtolower((string) $request->userAgent()), 'mobile') ? 'mobile' : 'desktop',
        ]);
    }
}
