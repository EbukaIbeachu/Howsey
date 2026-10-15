<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

class AutoEmailGenerator
{
    public const DEFAULT_DOMAIN = 'howsey.site';

    public static function domainForCompany(?Company $company = null): string
    {
        return self::domainForSlug($company?->slug);
    }

    public static function domainForSlug(?string $slug = null): string
    {
        $normalizedSlug = Str::slug((string) $slug);

        if ($normalizedSlug === '') {
            return self::DEFAULT_DOMAIN;
        }

        return $normalizedSlug.'.'.self::DEFAULT_DOMAIN;
    }

    public static function generate(string $fullName, ?string $role = null, string $domain = self::DEFAULT_DOMAIN): string
    {
        $normalizedName = self::normalizeName($fullName);
        $domain = trim(strtolower($domain));
        $reusableEmail = self::findReusableEmail($normalizedName, $role, $domain);

        if ($reusableEmail) {
            return $reusableEmail;
        }

        $localPart = self::buildLocalPart($normalizedName);
        $candidate = $localPart;
        $suffix = 2;

        while (self::emailExists($candidate.'@'.$domain)) {
            $candidate = $localPart.$suffix;
            $suffix++;
        }

        return $candidate.'@'.$domain;
    }

    private static function findReusableEmail(string $normalizedName, ?string $role = null, string $domain = self::DEFAULT_DOMAIN): ?string
    {
        if ($role === 'tenant') {
            $tenantEmail = Tenant::query()
                ->whereRaw('LOWER(full_name) = ?', [Str::lower($normalizedName)])
                ->whereRaw('LOWER(email) like ?', ['%@'.$domain])
                ->whereNotNull('email')
                ->value('email');

            if ($tenantEmail) {
                return Str::lower($tenantEmail);
            }
        }

        return null;
    }

    private static function emailExists(string $email): bool
    {
        return User::whereRaw('LOWER(email) = ?', [Str::lower($email)])->exists()
            || Tenant::whereRaw('LOWER(email) = ?', [Str::lower($email)])->exists();
    }

    private static function buildLocalPart(string $fullName): string
    {
        $parts = array_values(array_filter(array_map(function ($part) {
            return preg_replace('/[^a-z0-9]/', '', Str::lower($part));
        }, preg_split('/\s+/', $fullName) ?: [])));

        if ($parts === []) {
            return 'user';
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        $firstInitial = Str::substr($parts[0], 0, 1);
        $lastName = $parts[count($parts) - 1];

        return $firstInitial.'.'.$lastName;
    }

    private static function normalizeName(string $fullName): string
    {
        $ascii = Str::ascii(trim($fullName));
        $collapsedWhitespace = preg_replace('/\s+/', ' ', $ascii);

        return trim((string) $collapsedWhitespace);
    }
}
