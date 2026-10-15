<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

trait AccountingAuditable
{
    public static function logAccountingAction($actionType, $recordId, $recordType, $buildingId = null, $oldValues = null, $newValues = null)
    {
        $user = auth()->user();

        // Determine device type
        $userAgent = Request::header('User-Agent');
        $deviceType = self::getDeviceType($userAgent);

        AuditLog::create([
            'user_id' => $user ? $user->id : null,
            'role' => $user && $user->role ? $user->role->name : null,
            'action_type' => $actionType,
            'record_id' => $recordId,
            'record_type' => $recordType,
            'building_id' => $buildingId,
            'company_id' => $user?->company_id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
        ]);
    }

    private static function getDeviceType($userAgent)
    {
        $userAgent = strtolower($userAgent);
        $isMobile = str_contains($userAgent, 'mobile') ||
                    str_contains($userAgent, 'android') ||
                    str_contains($userAgent, 'iphone') ||
                    str_contains($userAgent, 'ipad');

        return $isMobile ? 'mobile' : 'desktop';
    }
}
