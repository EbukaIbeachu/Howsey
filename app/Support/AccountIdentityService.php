<?php

namespace App\Support;

use App\Models\User;

class AccountIdentityService
{
    public static function timestampFor(?User $user, string $timezone = 'Africa/Lagos'): string
    {
        $moment = $user?->created_at ?? now();

        return $moment->copy()->timezone($timezone)->format('Y-m-d H:i');
    }

    public static function accountIdWithTimestamp(?User $user, string $timezone = 'Africa/Lagos'): string
    {
        $accountId = $user?->email ?: 'unknown-account';

        return $accountId.' • '.self::timestampFor($user, $timezone);
    }

    public static function liveTimestampFor(string $timezone = 'Africa/Lagos'): string
    {
        return now()->timezone($timezone)->format('Y-m-d H:i');
    }

    public static function watermarkTextFor(?User $user, string $timezone = 'Africa/Lagos'): string
    {
        $accountId = $user?->email ?: 'unknown-account';

        return 'CONFIDENTIAL • '.$accountId.' • '.self::liveTimestampFor($timezone);
    }

    public static function watermarkTileUrl(string $text): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="960" height="220" viewBox="0 0 960 220">'
            .'<text x="24" y="138" fill="#000" fill-opacity="0.14" font-family="Arial, Helvetica, sans-serif" font-size="20" font-weight="800">'
            .htmlspecialchars($text, ENT_QUOTES | ENT_XML1)
            .'</text></svg>';

        return "url('data:image/svg+xml;charset=UTF-8,".rawurlencode($svg)."')";
    }
}
