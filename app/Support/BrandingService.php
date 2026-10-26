<?php

namespace App\Support;

use App\Models\Company;

class BrandingService
{
    public function palette(?Company $company): array
    {
        $primary = $company?->settings?->primary_color ?: '#7c3aed';
        $primaryText = $this->bestTextColor($primary);
        $primaryTextRgb = $this->hexToRgbString($primaryText, '255, 255, 255');
        $primaryTextMuted = $this->rgbaString($primaryText, $primaryText === '#ffffff' ? 0.82 : 0.74);
        $logoPath = $company?->settings?->logo_path;
        $defaultLogoUrl = url('/howsey.png');
        $companySlug = $company?->slug;
        $manifestUrl = $companySlug
            ? url('/manifest.webmanifest?company='.$companySlug)
            : url('/manifest.webmanifest');
        $iconUrl = url('/howsey.png');

        return [
            'app_name' => $company?->name ?: config('app.name', 'HOWSEY'),
            'primary_color' => $primary,
            'primary_hover' => $this->darken($primary, 0.12),
            'primary_text_color' => $primaryText,
            'primary_text_rgb' => $primaryTextRgb,
            'primary_text_muted' => $primaryTextMuted,
            'support_email' => $company?->settings?->support_email ?: config('mail.from.address'),
            'support_phone' => $company?->settings?->support_phone,
            'logo_path' => $logoPath,
            'logo_url' => $logoPath ? url('/storage/'.$logoPath) : $defaultLogoUrl,
            'receipt_header' => $company?->settings?->receipt_header ?: 'Official Payment Receipt',
            'email_header' => $company?->settings?->email_header ?: ($company?->name ?: config('app.name', 'HOWSEY')),
            'address' => $company?->settings?->address,
            'company_slug' => $companySlug,
            'company_portal_url' => $companySlug ? route('companies.portal', $companySlug) : url('/'),
            'company_login_url' => $companySlug ? route('companies.login', $companySlug) : route('login'),
            'company_staff_register_url' => $companySlug ? route('companies.register', $companySlug) : route('register'),
            'company_tenant_register_url' => $companySlug ? route('companies.register.tenant', $companySlug) : route('register.tenant'),
            'pwa_manifest_url' => $manifestUrl,
            'pwa_icon_url' => $iconUrl,
        ];
    }

    private function darken(string $hex, float $factor): string
    {
        $hex = $this->normalizeHex($hex);
        if ($hex === null) {
            return '#6d28d9';
        }

        $channels = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
        $darkened = array_map(function (int $channel) use ($factor) {
            return max(0, min(255, (int) round($channel * (1 - $factor))));
        }, $channels);

        return sprintf('#%02x%02x%02x', $darkened[0], $darkened[1], $darkened[2]);
    }

    private function bestTextColor(string $backgroundHex): string
    {
        $background = $this->normalizeHex($backgroundHex);

        if ($background === null) {
            return '#ffffff';
        }

        $whiteContrast = $this->contrastRatio($background, '#ffffff');
        $darkContrast = $this->contrastRatio($background, '#111827');

        return $darkContrast >= $whiteContrast ? '#111827' : '#ffffff';
    }

    private function contrastRatio(string $colorA, string $colorB): float
    {
        $l1 = $this->relativeLuminance($colorA);
        $l2 = $this->relativeLuminance($colorB);
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private function relativeLuminance(string $hex): float
    {
        $rgb = $this->hexToRgb($hex);

        if ($rgb === null) {
            return 0.0;
        }

        $channels = array_map(function (int $channel) {
            $value = $channel / 255;

            return $value <= 0.03928
                ? $value / 12.92
                : (($value + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
    }

    private function hexToRgbString(string $hex, string $fallback): string
    {
        $rgb = $this->hexToRgb($hex);

        return $rgb ? implode(', ', $rgb) : $fallback;
    }

    private function rgbaString(string $hex, float $alpha): string
    {
        $rgb = $this->hexToRgb($hex);

        if ($rgb === null) {
            return 'rgba(255, 255, 255, '.$alpha.')';
        }

        return sprintf('rgba(%d, %d, %d, %.2f)', $rgb[0], $rgb[1], $rgb[2], $alpha);
    }

    private function hexToRgb(string $hex): ?array
    {
        $normalized = $this->normalizeHex($hex);

        if ($normalized === null) {
            return null;
        }

        return array_map('hexdec', str_split(ltrim($normalized, '#'), 2));
    }

    private function normalizeHex(string $hex): ?string
    {
        $normalized = ltrim(trim($hex), '#');

        if (strlen($normalized) === 3) {
            $normalized = collect(str_split($normalized))
                ->map(fn (string $channel) => $channel.$channel)
                ->implode('');
        }

        if (! preg_match('/^[A-Fa-f0-9]{6}$/', $normalized)) {
            return null;
        }

        return '#'.strtolower($normalized);
    }
}
