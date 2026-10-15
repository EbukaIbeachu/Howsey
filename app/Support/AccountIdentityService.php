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

    public static function watermarkTextFor(?User $user, string $timezone = 'Africa/Lagos'): string
    {
        return 'CONFIDENTIAL • '.self::accountIdWithTimestamp($user, $timezone);
    }
}
