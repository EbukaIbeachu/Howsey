<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $portalCompany = $portalCompany ?? null;
        $brandName = $branding['app_name'] ?? ($portalCompany?->name ?? config('app.name', 'HOWSEY'));
        $brandLogo = $branding['logo_url'] ?? null;
        $brandPrimary = $branding['primary_color'] ?? '#7c3aed';
        $brandPrimaryHover = $branding['primary_hover'] ?? '#6d28d9';
        $brandPrimaryText = $branding['primary_text_color'] ?? '#ffffff';
        $brandPrimaryTextRgb = $branding['primary_text_rgb'] ?? '255, 255, 255';
        $brandPrimaryTextMuted = $branding['primary_text_muted'] ?? 'rgba(255, 255, 255, 0.82)';
        $brandPrimaryHex = ltrim($brandPrimary, '#');
        $brandPrimaryRgb = strlen($brandPrimaryHex) === 6
            ? implode(', ', array_map('hexdec', str_split($brandPrimaryHex, 2)))
            : '124, 58, 237';
        $portalHomeUrl = $portalCompany ? route('companies.portal', $portalCompany->slug) : url('/');
        $portalLoginUrl = $portalCompany ? route('companies.login', $portalCompany->slug) : route('login');
        $staffRegisterUrl = $portalCompany ? route('companies.register', $portalCompany->slug) : route('register');
        $tenantRegisterUrl = $portalCompany ? route('companies.register.tenant', $portalCompany->slug) : route('register.tenant');
        $companyCreateUrl = route('companies.public.create');
        $existingWorkspaceAdminLoginUrl = route('login');
        $isMainHowseyHome = ! $portalCompany;
        $enablePortalPwaUi = (bool) $portalCompany;
        $companyHasUploadedLogo = filled(optional($portalCompany?->settings)->logo_path);
        $portalQrUrl = $portalCompany
            ? 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='.rawurlencode(route('companies.portal', $portalCompany->slug))
            : null;
        // Public HOWSEY homepage SEO. The canonical host is the production domain,
        // never APP_URL (which is 127.0.0.1 during local development).
        $seoTitle = 'HOWSEY | Property Management Software for Landlords and Property Managers';
        $seoDescription = 'Manage properties, tenants, rent and payment records in one organized workspace with HOWSEY property management software.';
        $canonicalUrl = 'https://howsey.site/';
        $ogImageUrl = 'https://howsey.site/howsey.png';
        $pageTitle = $portalCompany ? $brandName.' Workspace' : $seoTitle;
        $heroTitle = $portalCompany ? 'Welcome to '.$brandName : 'Property management, made easier.';
        $heroSubtitle = $portalCompany
            ? 'Welcome to your company workspace. Register staff, onboard tenants, and manage everything from one simple HOWSEY experience across desktop and mobile.'
            : 'Manage your properties, tenants, rent and payments from one organized workspace.';
        $getStartedHelp = $portalCompany
            ? 'Use Get Started to register new managers, accountants, or tenants directly into the '.$brandName.' workspace.'
            : "Set up your company's workspace to get started.";
        $companyCreateUrlMain = route('companies.public.create');
        $adminLoginUrlMain = route('login');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    @if($isMainHowseyHome)
        {{-- Public marketing metadata. Kept off company portal pages to avoid duplicate canonical tags. --}}
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="HOWSEY">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:image" content="{{ $ogImageUrl }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription }}">
        <meta name="twitter:image" content="{{ $ogImageUrl }}">
        <link rel="icon" type="image/png" href="{{ url('/howsey.png') }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    @if($enablePortalPwaUi)
        <link rel="manifest" href="{{ $branding['pwa_manifest_url'] ?? url('/manifest.webmanifest') }}">
        <meta name="theme-color" content="{{ $branding['primary_color'] ?? '#7c3aed' }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <link rel="icon" href="{{ $branding['pwa_icon_url'] ?? url('/howsey.png') }}" type="image/png">
        <link rel="apple-touch-icon" sizes="512x512" href="{{ $branding['pwa_icon_url'] ?? url('/howsey.png') }}">
    @endif
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-primary: {{ $brandPrimary }};
            --brand-primary-hover: {{ $brandPrimaryHover }};
            --brand-primary-rgb: {{ $brandPrimaryRgb }};
            --on-brand: {{ $brandPrimaryText }};
            --on-brand-rgb: {{ $brandPrimaryTextRgb }};
            --on-brand-muted: {{ $brandPrimaryTextMuted }};
        }
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #fdfbf7; /* Off-white */
            border-top: 5px solid var(--brand-primary);
            overflow-x: hidden;
        }
        .brand-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .brand-logo {
            width: 2.4rem;
            height: 2.4rem;
            object-fit: contain;
            border-radius: 0.85rem;
            background: rgba(255,255,255,0.92);
            padding: 0.25rem;
            box-shadow: 0 0.35rem 1rem rgba(15, 23, 42, 0.18);
        }
        .brand-logo-photo {
            width: 3.4rem;
            height: 3.4rem;
            padding: 0;
            object-fit: cover;
            background: rgba(255,255,255,0.18);
            border: 2px solid rgba(255,255,255,0.78);
            box-shadow: 0 0.85rem 1.75rem rgba(15, 23, 42, 0.32);
        }
        .hero-section {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            background-color: var(--brand-primary);
            height: calc(100vh - 5px); /* Adjust for border */
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--on-brand);
            text-align: center;
        }
        .hero-section::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(rgba(18, 10, 29, 0.36), rgba(18, 10, 29, 0.56));
            z-index: 1;
        }
        .hero-media {
            position: absolute;
            inset: 0;
            z-index: 0;
            display: block;
            pointer-events: none;
        }
        .hero-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            width: min(100%, 800px);
            padding: 1.5rem;
        }
        .hero-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: clamp(2.4rem, 5vw, 3.5rem);
            font-weight: 700;
            line-height: 1.15;
            margin-bottom: 20px;
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(0,0,0,0.45);
        }
        .hero-subtitle {
            font-family: 'Nunito', sans-serif;
            font-size: clamp(1.05rem, 2.4vw, 1.5rem);
            font-weight: 500;
            line-height: 1.6;
            margin-bottom: 30px;
            color: rgba(255, 255, 255, 0.92);
            text-shadow: 0 1px 4px rgba(0,0,0,0.4);
        }
        .btn-custom {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
            color: var(--on-brand);
            padding: 12px 30px;
            font-size: 1.1rem;
            border-radius: 50px;
            transition: all 0.3s;
            text-decoration: none;
        }
        .btn-custom:hover {
            background-color: var(--brand-primary-hover);
            border-color: var(--brand-primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(var(--brand-primary-rgb), 0.4);
            color: var(--on-brand);
        }
        .btn-mobile-small {
            padding: 6px 16px;
            font-size: 0.9rem;
        }
        .navbar-custom {
            background: linear-gradient(180deg, rgba(var(--brand-primary-rgb), 0.55), rgba(var(--brand-primary-rgb), 0.08));
            backdrop-filter: blur(10px);
            position: absolute;
            top: 0;
            width: 100%;
            z-index: 10;
            padding: 20px 0;
        }
        .navbar-brand {
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            font-weight: bold;
            color: var(--on-brand) !important;
        }
        .navbar-nav {
            align-items: center;
            gap: 0.5rem;
        }
        .nav-link {
            color: var(--on-brand-muted) !important;
            font-weight: 600;
            margin-left: 0;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        .nav-link:hover {
            color: var(--on-brand) !important;
            text-shadow: 0 0 18px rgba(var(--brand-primary-rgb), 0.75);
        }
        .navbar-custom .navbar-toggler {
            border-color: rgba(var(--on-brand-rgb), 0.38);
            color: var(--on-brand);
        }
        .navbar-custom .navbar-toggler:focus {
            box-shadow: 0 0 0 0.25rem rgba(var(--on-brand-rgb), 0.18);
        }
        .navbar-custom .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba({{ $brandPrimaryTextRgb }}, 0.95)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        .hero-section .text-white,
        .hero-section a.text-white,
        .hero-section .small,
        .hero-section .small strong,
        .hero-section .navbar-brand,
        .hero-section .nav-link {
            color: var(--on-brand) !important;
        }
        .hero-section .text-white-50,
        .hero-section .small.text-white-50 {
            color: var(--on-brand-muted) !important;
        }
        .portal-links-card,
        .portal-links-card-mobile {
            color: var(--on-brand);
        }
        .portal-links-card a,
        .portal-links-card-mobile a {
            color: var(--on-brand) !important;
            text-decoration-color: rgba(var(--on-brand-rgb), 0.45);
        }
        /* Company URLs sit on top of the hero image, so keep them white. */
        .hero-section .portal-links-card .small a,
        .hero-section .portal-links-card-mobile .small a {
            color: #ffffff !important;
            text-decoration-color: rgba(255, 255, 255, 0.45);
        }
        .role-option-card {
            border: 1px solid rgba(var(--brand-primary-rgb), 0.12);
            border-radius: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            text-decoration: none;
            color: inherit;
        }
        .role-option-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.75rem 1.5rem rgba(var(--brand-primary-rgb), 0.12);
            border-color: rgba(var(--brand-primary-rgb), 0.35);
            color: inherit;
        }
        .portal-links-card {
            max-width: min(100%, 34rem);
            margin-inline: auto;
        }
        .portal-links-card-mobile {
            max-width: min(100%, 32rem);
            margin: 1rem auto 0;
        }
        .portal-links-card a {
            word-break: break-word;
        }
        .portal-qr-image {
            width: min(100%, 180px);
            height: auto;
        }
        @media (min-width: 769px) {
            .portal-links-card {
                position: absolute;
                right: clamp(1rem, 4vw, 3rem);
                bottom: clamp(1rem, 4vw, 2.5rem);
                width: min(24rem, calc(100vw - 2rem));
                margin-inline: 0;
                z-index: 2;
                box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.18);
            }
        }
        .text-primary {
            color: var(--brand-primary) !important;
        }
        .modal-content .fw-bold.text-primary {
            color: var(--brand-primary) !important;
        }
        .portal-pwa-actions {
            position: fixed;
            left: 1rem;
            /* Sits above the masked HOWSEY badge pinned at the bottom-left corner. */
            bottom: 5rem;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-width: calc(100vw - 2rem);
        }
        .portal-pwa-actions .btn {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
            color: var(--on-brand);
            border-radius: 999px;
            box-shadow: 0 0.85rem 1.8rem rgba(var(--brand-primary-rgb), 0.24);
            display: none;
            align-items: center;
            gap: 0.5rem;
        }
        .portal-pwa-actions .btn:hover,
        .portal-pwa-actions .btn:focus,
        .portal-pwa-actions .btn:active {
            background-color: var(--brand-primary-hover);
            border-color: var(--brand-primary-hover);
            color: var(--on-brand);
            box-shadow: 0 1rem 2rem rgba(var(--brand-primary-rgb), 0.3);
        }
        .modal-dialog {
            margin: 1rem;
        }
        @media (max-width: 991.98px) {
            .navbar-custom {
                padding: 0.9rem 0;
            }
            .navbar-collapse {
                margin-top: 0.85rem;
                padding: 0.9rem 1rem;
                border-radius: 1.25rem;
                background: rgba(var(--brand-primary-rgb), 0.82);
                box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.18);
                color: var(--on-brand);
            }
            .navbar-nav {
                align-items: stretch;
            }
            .navbar-nav .btn-custom,
            .navbar-nav .nav-link {
                width: 100%;
                justify-content: center;
                text-align: center;
            }
        }
        @media (max-width: 768px) {
            .hero-section {
                min-height: calc(100svh - 5px);
                height: auto;
                padding: 6rem 0 3rem;
                flex-direction: column;
                justify-content: center;
            }
            .hero-section::before {
                background: linear-gradient(rgba(18, 10, 29, 0.18), rgba(18, 10, 29, 0.42));
            }
            .hero-media img {
                object-position: center top;
            }
            .navbar-custom {
                background: linear-gradient(180deg, rgba(var(--brand-primary-rgb), 0.72), rgba(var(--brand-primary-rgb), 0.14));
            }
            .brand-logo-photo {
                width: 2.6rem;
                height: 2.6rem;
            }
            .hero-content {
                max-width: 100%;
                padding: 1rem 1rem 1.5rem;
            }
            .hero-title {
                margin-bottom: 0.9rem;
                line-height: 1.1;
            }
            .hero-subtitle {
                margin-bottom: 1.25rem;
                line-height: 1.45;
            }
            .btn-custom.btn-lg,
            .btn-mobile-small {
                width: 100%;
                max-width: 22rem;
            }
            .portal-links-card {
                position: static;
                width: 100%;
                max-width: min(100%, 32rem);
                margin-top: 1rem;
                padding: 1rem !important;
                text-align: left;
            }
            .portal-links-card-mobile {
                width: 100%;
                padding: 1rem !important;
                text-align: left;
            }
            .portal-links-card .small {
                font-size: 0.86rem;
                line-height: 1.45;
            }
            .portal-links-card-mobile .small {
                font-size: 0.86rem;
                line-height: 1.45;
            }
            .portal-pwa-actions {
                left: 0.75rem;
                right: 0.75rem;
                bottom: calc(3.75rem + env(safe-area-inset-bottom, 0px));
                max-width: none;
            }
            .portal-pwa-actions .btn {
                width: 100%;
                justify-content: center;
            }
            .modal-dialog {
                margin: 0.75rem;
            }
            .modal-content {
                border-radius: 1.25rem;
            }
        }
        @media (max-width: 576px) {
            .hero-section {
                padding-top: 5.5rem;
            }
            .brand-logo {
                width: 2rem;
                height: 2rem;
            }
            .hero-content {
                padding-inline: 0.9rem;
            }
            .hero-title {
                font-size: 2rem;
            }
            .hero-subtitle {
                font-size: 0.98rem;
            }
            .portal-links-card .d-flex {
                align-items: flex-start !important;
            }
        }

        /* ==================================================================
           Public HOWSEY homepage marketing sections.
           Every rule below is scoped to .hp-body, which is only added on the
           main HOWSEY home ($isMainHowseyHome === true). Company portal pages
           never receive these classes, so their behaviour is unchanged.
           ================================================================== */
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 1rem;
        }
        .hp-body {
            --hp-ink: #1e1b2e;
            --hp-muted: #5b5670;
            --hp-line: rgba(30, 27, 46, 0.10);
        }
        /* See-through header: it floats over the hero image instead of sitting on
           a filled bar, so there is no demarcating edge or divider shadow. The
           only mark is a soft top-down scrim that fades to nothing, which keeps
           the white wordmark and menu button readable over the photograph. */
        .hp-body .navbar-custom {
            background: linear-gradient(180deg, rgba(18, 10, 29, 0.34), rgba(18, 10, 29, 0));
            box-shadow: none;
            backdrop-filter: none;
        }
        /* Full-bleed header bar: the brand sits near the left edge and the menu
           button near the right, rather than being pulled inward by the centred
           Bootstrap container. */
        .hp-body .navbar-custom > .container {
            max-width: 100%;
            padding-left: 1.5rem;
            padding-right: 0.75rem;
        }
        .hp-body .hero-section {
            height: calc(100vh - 5px);
            min-height: 620px;
        }
        .hp-nav-links .nav-link {
            color: rgba(var(--on-brand-rgb), 0.92) !important;
        }
        .hp-nav-links .nav-link:hover,
        .hp-nav-links .nav-link:focus-visible {
            color: var(--on-brand) !important;
        }
        .hp-section {
            padding: 4.5rem 0;
        }
        .hp-section--tint {
            background: #f6f3ee;
        }
        .hp-eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--brand-primary);
            margin-bottom: 0.6rem;
        }
        .hp-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 700;
            font-size: clamp(1.7rem, 3vw, 2.4rem);
            color: var(--hp-ink);
            margin-bottom: 0.75rem;
        }
        .hp-sub {
            color: var(--hp-muted);
            font-size: 1.02rem;
            max-width: 46rem;
            margin-inline: auto;
        }
        .hp-card {
            height: 100%;
            background: #fff;
            border: 1px solid var(--hp-line);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .hp-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.9rem 1.8rem rgba(15, 23, 42, 0.10);
            border-color: rgba(var(--brand-primary-rgb), 0.35);
        }
        .hp-card h3 {
            font-size: 1.06rem;
            font-weight: 700;
            color: var(--hp-ink);
            margin: 0 0 0.5rem;
        }
        .hp-card p {
            color: var(--hp-muted);
            font-size: 0.95rem;
            margin: 0;
        }
        .hp-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.85rem;
            background: rgba(var(--brand-primary-rgb), 0.12);
            color: var(--brand-primary);
            font-size: 1.35rem;
            margin-bottom: 0.9rem;
        }
        .hp-trust {
            background: #fff;
            border-top: 1px solid var(--hp-line);
            border-bottom: 1px solid var(--hp-line);
        }
        .hp-trust-item {
            display: flex;
            gap: 0.85rem;
            align-items: flex-start;
        }
        .hp-trust-item i {
            color: var(--brand-primary);
            font-size: 1.2rem;
            line-height: 1.5;
        }
        .hp-trust-item span {
            color: var(--hp-ink);
            font-weight: 600;
            font-size: 0.95rem;
        }
        .hp-step {
            background: #fff;
            border: 1px solid var(--hp-line);
            border-radius: 1rem;
            padding: 1.75rem 1.5rem;
            height: 100%;
        }
        .hp-step-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.4rem;
            height: 2.4rem;
            border-radius: 50%;
            background: var(--brand-primary);
            color: var(--on-brand);
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .hp-preview-frame {
            background: #fff;
            border: 1px solid var(--hp-line);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 1.2rem 2.4rem rgba(15, 23, 42, 0.10);
        }
        .hp-preview-bar {
            display: flex;
            gap: 0.4rem;
            padding: 0.7rem 0.9rem;
            border-bottom: 1px solid var(--hp-line);
            background: #faf9f7;
        }
        .hp-preview-bar span {
            width: 0.65rem;
            height: 0.65rem;
            border-radius: 50%;
            background: #dcd8e0;
        }
        .hp-preview-shot {
            display: block;
            width: 100%;
            height: auto;
            background: #faf9f7;
        }
        .hp-preview-caption {
            margin: 0;
            padding: 0.75rem 1rem;
            border-top: 1px solid var(--hp-line);
            font-size: 0.85rem;
            color: var(--hp-muted);
            text-align: center;
        }
        .hp-preview-tile {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: #fff;
        }
        .hp-preview-tile-media {
            display: flex;
            align-items: center;
            justify-content: center;
            aspect-ratio: 16 / 9;
            padding: 0.65rem;
            background: #faf9f7;
        }
        .hp-preview-tile-media img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            border-radius: 0.5rem;
            background: #faf9f7;
        }
        .hp-preview-tile-caption {
            padding: 0.85rem 1rem 1rem;
            border-top: 1px solid var(--hp-line);
        }
        .hp-preview-tile-caption strong {
            display: block;
            margin-bottom: 0.2rem;
            color: var(--hp-ink);
            font-size: 0.95rem;
        }
        .hp-preview-tile-caption small {
            display: block;
            color: var(--hp-muted);
            line-height: 1.45;
        }
        .hp-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 0.45rem;
            min-height: 15rem;
            padding: 2rem 1.25rem;
            background: repeating-linear-gradient(135deg, #f7f5f1, #f7f5f1 12px, #f1eee8 12px, #f1eee8 24px);
            color: var(--hp-muted);
        }
        .hp-placeholder i {
            font-size: 1.8rem;
            color: var(--brand-primary);
            opacity: 0.7;
        }
        .hp-placeholder strong {
            color: var(--hp-ink);
            font-size: 0.95rem;
        }
        .hp-placeholder small {
            font-size: 0.8rem;
            max-width: 22rem;
        }
        .hp-placeholder--sm {
            min-height: 8.5rem;
        }
        .hp-pricing-card {
            background: #fff;
            border: 1px solid var(--hp-line);
            border-radius: 1rem;
            padding: 2rem;
            max-width: 38rem;
            margin-inline: auto;
            text-align: center;
            box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.08);
        }
        .hp-accordion .accordion-item {
            border: 1px solid var(--hp-line);
            border-radius: 0.85rem !important;
            margin-bottom: 0.75rem;
            overflow: hidden;
            background: #fff;
        }
        .hp-accordion .accordion-button {
            font-weight: 700;
            color: var(--hp-ink);
            background: #fff;
            box-shadow: none;
            padding: 1.05rem 1.15rem;
        }
        .hp-accordion .accordion-button:not(.collapsed) {
            color: var(--brand-primary);
            background: rgba(var(--brand-primary-rgb), 0.06);
        }
        .hp-accordion .accordion-button:focus-visible {
            outline: 3px solid rgba(var(--brand-primary-rgb), 0.55);
            outline-offset: 2px;
            z-index: 3;
        }
        .hp-accordion .accordion-body {
            color: var(--hp-muted);
            font-size: 0.96rem;
        }
        .hp-cta {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-hover));
            color: var(--on-brand);
            border-radius: 1.25rem;
            padding: 3rem 1.5rem;
            text-align: center;
        }
        .hp-cta h2 {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 700;
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            color: var(--on-brand);
            margin-bottom: 0.75rem;
        }
        .hp-cta .btn-custom {
            background: #fff;
            border-color: #fff;
            color: var(--brand-primary);
        }
        .hp-cta .btn-custom:hover,
        .hp-cta .btn-custom:focus-visible {
            background: rgba(255, 255, 255, 0.9);
            border-color: rgba(255, 255, 255, 0.9);
            color: var(--brand-primary);
        }
        .hp-btn-ghost {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.7);
            color: #fff;
            padding: 12px 30px;
            font-size: 1.1rem;
            border-radius: 50px;
            text-decoration: none;
            transition: background 0.2s ease, border-color 0.2s ease;
        }
        .hp-btn-ghost:hover,
        .hp-btn-ghost:focus-visible {
            background: rgba(255, 255, 255, 0.16);
            border-color: #fff;
            color: #fff;
        }
        .hp-footer {
            background: #17141f;
            color: #c9c5d4;
            padding: 3rem 0 1.5rem;
        }
        .hp-footer h2 {
            font-size: 0.95rem;
            color: #fff;
            font-weight: 700;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .hp-footer a {
            color: #c9c5d4;
            text-decoration: none;
        }
        .hp-footer a:hover,
        .hp-footer a:focus-visible {
            color: #fff;
            text-decoration: underline;
        }
        .hp-footer-brand {
            color: #fff;
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 700;
            font-size: 1.3rem;
        }
        .hp-footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            margin-top: 2rem;
            padding-top: 1.25rem;
            font-size: 0.85rem;
        }
        .hp-body :focus-visible {
            outline: 3px solid rgba(var(--brand-primary-rgb), 0.6);
            outline-offset: 2px;
        }
        @media (max-width: 991.98px) {
            /* Below the desktop breakpoint the menu button takes over from the
               inline links, so tighten the bar right up to the screen edges. */
            .hp-body .navbar-custom > .container {
                padding-left: 0.6rem;
                padding-right: 0.35rem;
            }
            .hp-body .navbar-collapse {
                background: var(--brand-primary-hover);
            }
            .hp-nav-links .nav-link {
                text-align: center;
            }
        }
        @media (max-width: 768px) {
            .hp-body .hero-section {
                height: auto;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }
            .hp-card {
                transition: none;
            }
            .hp-card:hover {
                transform: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body class="{{ $isMainHowseyHome ? 'hp-body' : '' }}">
    <nav class="navbar navbar-expand-lg navbar-custom navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ $portalHomeUrl }}">
                @if($brandLogo)
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }} logo" class="brand-logo {{ $companyHasUploadedLogo ? 'brand-logo-photo' : '' }}">
                @else
                    <i class="bi bi-building"></i>
                @endif
                <span class="brand-title">{{ $brandName }}</span>
            </a>
            
            <div class="d-flex align-items-center order-lg-2">
                @if (Route::has('login'))
                    @auth
                        @php
                            $roleHomeUrl = Auth::user()->isAccountant() ? route('accounting.index') : route('dashboard');
                            $roleHomeLabel = Auth::user()->isAdmin()
                                ? 'Admin Dashboard'
                                : (Auth::user()->isManager()
                                    ? 'Manager Dashboard'
                                    : (Auth::user()->isTenant() ? 'Tenant Portal' : 'Accounting'));
                        @endphp
                        <a href="{{ $roleHomeUrl }}" class="btn btn-custom btn-mobile-small ms-1 d-lg-none">{{ $roleHomeLabel }}</a>
                    @else
                        @unless($isMainHowseyHome)
                            <a href="{{ $portalLoginUrl }}" class="nav-link d-lg-none me-3">Log in</a>
                        @endunless
                    @endauth
                @endif
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    @if($isMainHowseyHome && ! auth()->check())
                        {{-- Marketing navigation for the public homepage. Company portals
                             and authenticated sessions keep their original navigation below. --}}
                        <li class="nav-item hp-nav-links"><a class="nav-link" href="#features">Features</a></li>
                        <li class="nav-item hp-nav-links"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                        <li class="nav-item hp-nav-links"><a class="nav-link" href="#who-its-for">Who It's For</a></li>
                        <li class="nav-item hp-nav-links"><a class="nav-link" href="#pricing">Pricing</a></li>
                        <li class="nav-item hp-nav-links"><a class="nav-link" href="#faqs">FAQs</a></li>
                        <li class="nav-item hp-nav-links"><a class="nav-link" href="#contact">Contact</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ $adminLoginUrlMain }}">Sign in</a></li>
                        <li class="nav-item d-flex align-items-center">
                            <a href="{{ $companyCreateUrlMain }}" class="btn btn-custom ms-1">Get Started</a>
                        </li>
                    @else
                    @if (Route::has('login'))
                        @auth
                            <li class="nav-item d-none d-lg-block">
                                <a href="{{ $roleHomeUrl }}" class="btn btn-custom ms-1">{{ $roleHomeLabel }}</a>
                            </li>
                        @else
                            @unless($isMainHowseyHome)
                                <li class="nav-item d-none d-lg-block">
                                    <a href="{{ $portalLoginUrl }}" class="nav-link">Log in</a>
                                </li>
                            @endunless
                            @if (Route::has('register'))
                                <li class="nav-item d-flex align-items-center">
                                    <a href="https://instagram.com/howsey.site" target="_blank" rel="noopener noreferrer"
                                       class="d-inline-flex align-items-center justify-content-center me-2 welcome-instagram-link"
                                       style="width: 2.25rem; height: 2.25rem; border-radius: 0.5rem; background: rgba(var(--brand-primary-rgb), 0.22); color: #fff; text-decoration: none; border: 1px solid rgba(255,255,255,0.25); transition: background 0.2s ease, transform 0.2s ease;"
                                       title="Follow HOWSEY on Instagram" aria-label="Follow HOWSEY on Instagram"
                                       onmouseover="this.style.background='rgba(var(--brand-primary-rgb), 0.5)'; this.style.transform='scale(1.08)';"
                                       onmouseout="this.style.background='rgba(var(--brand-primary-rgb), 0.22)'; this.style.transform='scale(1)';">
                                        <i class="bi bi-instagram fs-5"></i>
                                    </a>
                                    <button type="button" class="btn btn-custom ms-1" data-bs-toggle="modal" data-bs-target="#getStartedModal">Get Started</button>
                                </li>
                            @endif
                        @endauth
                    @endif
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <main>
    <div class="hero-section">
        <picture class="hero-media" aria-hidden="true">
            <source media="(max-width: 768px)" srcset="{{ asset('assets/images/hero-bg-mobile.jpg') }}">
            <img src="{{ asset('assets/images/hero-bg.jpg') }}" alt="" fetchpriority="high">
        </picture>
        <div class="hero-content">
            @if(session('success'))
                <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
            @endif
            <h1 class="hero-title">{{ $heroTitle }}</h1>
            <p class="hero-subtitle">{{ $heroSubtitle }}</p>
            @if($isMainHowseyHome && ! auth()->check())
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="{{ $companyCreateUrlMain }}" class="btn btn-custom btn-lg">Create your company</a>
                    <a href="{{ $adminLoginUrlMain }}" class="hp-btn-ghost btn-lg">Sign in</a>
                </div>
                <p class="small mt-3 mb-0 text-white-50">{{ $getStartedHelp }}</p>
            @else
                @auth
                    <a href="{{ $roleHomeUrl }}" class="btn btn-custom btn-lg">{{ $roleHomeLabel }}</a>
                @else
                    @if (Route::has('register'))
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <button type="button" class="btn btn-custom btn-lg" data-bs-toggle="modal" data-bs-target="#getStartedModal">Get Started</button>
                        </div>
                        <p class="small mt-3 mb-0 text-white-50">{{ $getStartedHelp }}</p>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-custom btn-lg">Login to Dashboard</a>
                    @endif
                @endauth
            @endif
            @if($portalCompany)
                <div class="portal-links-card-mobile d-md-none mt-4 bg-white bg-opacity-10 border border-light border-opacity-25 rounded-4 p-3 text-start">
                    <div class="text-center">
                        <div class="small text-white-50 text-uppercase fw-semibold mb-2">Scan Company Homepage</div>
                        <img src="{{ $portalQrUrl }}" alt="{{ $brandName }} workspace QR code" class="portal-qr-image img-fluid bg-white rounded-3 p-2" loading="lazy" decoding="async">
                        <div class="small text-white-50 mt-2">Scan to open {{ $brandName }} on this device.</div>
                    </div>
                    <div class="mt-3 pt-3 border-top border-light border-opacity-25">
                        <div class="small text-white-50 text-uppercase fw-semibold mb-2 d-flex align-items-center gap-2">
                            <span>Workspace Links</span>
                            <button
                                type="button"
                                class="btn btn-sm btn-light rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                                style="width: 1.5rem; height: 1.5rem;"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Save these company links and share the correct one with your staff and tenants so they always enter the right workspace."
                                aria-label="Workspace link help"
                            >?</button>
                        </div>
                        <div class="small"><strong>Portal:</strong> <a href="{{ route('companies.portal', $portalCompany->slug) }}" class="text-white">{{ route('companies.portal', $portalCompany->slug) }}</a></div>
                        <div class="small"><strong>Login:</strong> <a href="{{ route('companies.login', $portalCompany->slug) }}" class="text-white">{{ route('companies.login', $portalCompany->slug) }}</a></div>
                        <div class="small"><strong>Staff Registration:</strong> <a href="{{ route('companies.register', $portalCompany->slug) }}" class="text-white">{{ route('companies.register', $portalCompany->slug) }}</a></div>
                        <div class="small"><strong>Tenant Registration:</strong> <a href="{{ route('companies.register.tenant', $portalCompany->slug) }}" class="text-white">{{ route('companies.register.tenant', $portalCompany->slug) }}</a></div>
                    </div>
                </div>
            @endif
        </div>
        @if($portalCompany)
            <div class="portal-links-card d-none d-md-block mt-4 bg-white bg-opacity-10 border border-light border-opacity-25 rounded-4 p-3 text-start">
                <div class="small text-white-50 text-uppercase fw-semibold mb-2 d-flex align-items-center gap-2">
                    <span>Workspace Links</span>
                    <button
                        type="button"
                        class="btn btn-sm btn-light rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                        style="width: 1.5rem; height: 1.5rem;"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="Save these company links and share the correct one with your staff and tenants so they always enter the right workspace."
                        aria-label="Workspace link help"
                    >?</button>
                </div>
                <div class="small"><strong>Portal:</strong> <a href="{{ route('companies.portal', $portalCompany->slug) }}" class="text-white">{{ route('companies.portal', $portalCompany->slug) }}</a></div>
                <div class="small"><strong>Login:</strong> <a href="{{ route('companies.login', $portalCompany->slug) }}" class="text-white">{{ route('companies.login', $portalCompany->slug) }}</a></div>
                <div class="small"><strong>Staff Registration:</strong> <a href="{{ route('companies.register', $portalCompany->slug) }}" class="text-white">{{ route('companies.register', $portalCompany->slug) }}</a></div>
                <div class="small"><strong>Tenant Registration:</strong> <a href="{{ route('companies.register.tenant', $portalCompany->slug) }}" class="text-white">{{ route('companies.register.tenant', $portalCompany->slug) }}</a></div>
                <div class="mt-3 pt-3 border-top border-light border-opacity-25 text-center">
                    <div class="small text-white-50 text-uppercase fw-semibold mb-2">Scan Company Homepage</div>
                    <img src="{{ $portalQrUrl }}" alt="{{ $brandName }} workspace QR code" class="portal-qr-image img-fluid bg-white rounded-3 p-2" loading="lazy" decoding="async">
                    <div class="small text-white-50 mt-2">Scan to open {{ $brandName }} on this device.</div>
                </div>
            </div>
        @endif
    </div>
    @if($isMainHowseyHome)
    {{-- ==================================================================
         Public marketing sections. Rendered only on the main HOWSEY home.
         Company portals never reach this block.
         ================================================================== --}}

    {{-- SECTION 3 — Trust and product reassurance --}}
    <section class="hp-trust py-4" aria-label="How HOWSEY is built">
        <div class="container">
            <div class="row g-3 g-md-4">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="hp-trust-item">
                        <i class="bi bi-buildings" aria-hidden="true"></i>
                        <span>Dedicated company workspaces</span>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="hp-trust-item">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                        <span>Role-based user access</span>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="hp-trust-item">
                        <i class="bi bi-journal-text" aria-hidden="true"></i>
                        <span>Organized rent and payment records</span>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="hp-trust-item">
                        <i class="bi bi-person-badge" aria-hidden="true"></i>
                        <span>Tenant-facing access</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 4 — Core features --}}
    <section id="features" class="hp-section">
        <div class="container">
            <div class="text-center mb-5">
                <div class="hp-eyebrow">Features</div>
                <h2 class="hp-title">Everything you need to stay organized.</h2>
                <p class="hp-sub">Bring essential property management activities together instead of maintaining disconnected records.</p>
            </div>
            <div class="row g-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="hp-card">
                        <span class="hp-icon"><i class="bi bi-buildings" aria-hidden="true"></i></span>
                        <h3>Property and Unit Management</h3>
                        <p>Maintain structured records of buildings, properties and individual units.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="hp-card">
                        <span class="hp-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                        <h3>Tenant Management</h3>
                        <p>Keep tenant information organized and associated with the appropriate property or unit.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="hp-card">
                        <span class="hp-icon"><i class="bi bi-cash-coin" aria-hidden="true"></i></span>
                        <h3>Rent and Payment Tracking</h3>
                        <p>Monitor rent obligations, payment records and outstanding balances in one place.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="hp-card">
                        <span class="hp-icon"><i class="bi bi-person-lock" aria-hidden="true"></i></span>
                        <h3>Staff Accounts and Permissions</h3>
                        <p>Provide staff with appropriate access based on their responsibilities.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="hp-card">
                        <span class="hp-icon"><i class="bi bi-grid-1x2" aria-hidden="true"></i></span>
                        <h3>Your Company's Workspace</h3>
                        <p>Operate through a dedicated company-specific HOWSEY workspace.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="hp-card">
                        <span class="hp-icon"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
                        <h3>Reports and Operational Visibility</h3>
                        <p>Access the available dashboards, reports and operational records.</p>
                    </div>
                </div>
            </div>
            <div class="hp-card mt-4 d-flex flex-column flex-md-row align-items-md-center gap-3">
                <span class="hp-icon mb-0"><i class="bi bi-tools" aria-hidden="true"></i></span>
                <div>
                    <h3>Maintenance management</h3>
                    <p>Log and track maintenance activity for the properties in your workspace.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 5 — Product preview --}}
    <section id="preview" class="hp-section hp-section--tint">
        <div class="container">
            <div class="text-center mb-5">
                <div class="hp-eyebrow">Product preview</div>
                <h2 class="hp-title">See your property operations more clearly.</h2>
                <p class="hp-sub">Dashboards, tenant records, rent tracking and maintenance activity in one workspace.</p>
            </div>
            <figure class="hp-preview-frame mb-4">
                <div class="hp-preview-bar" aria-hidden="true"><span></span><span></span><span></span></div>
                <img src="{{ asset('assets/images/howsey-dashboard-preview.png') }}"
                     class="hp-preview-shot"
                     alt="HOWSEY dashboard showing property, tenant, rent and payment records for a demonstration company."
                     width="1335" height="690" loading="lazy" decoding="async">
                <figcaption class="hp-preview-caption">HOWSEY dashboard shown with synthetic demonstration data. No real tenant or financial information is displayed.</figcaption>
            </figure>
            <div class="row g-4">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="hp-preview-frame h-100">
                        <figure class="hp-preview-tile mb-0">
                            <div class="hp-preview-tile-media">
                                <img src="{{ asset('assets/images/landing-preview-tenants.png') }}"
                                     alt="HOWSEY tenant records view showing tenant names, units, contact details and status."
                                     width="1346" height="683" loading="lazy" decoding="async">
                            </div>
                            <figcaption class="hp-preview-tile-caption">
                                <strong>Tenant records</strong>
                                <small>Review tenant names, assigned units, contact details and active occupancy status at a glance.</small>
                            </figcaption>
                        </figure>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="hp-preview-frame h-100">
                        <figure class="hp-preview-tile mb-0">
                            <div class="hp-preview-tile-media">
                                <img src="{{ asset('assets/images/landing-preview-payments.png') }}"
                                     alt="HOWSEY payments view showing rent amounts, payment methods and completion status."
                                     width="1343" height="690" loading="lazy" decoding="async">
                            </div>
                            <figcaption class="hp-preview-tile-caption">
                                <strong>Rent and payments</strong>
                                <small>Track rent entries, payment methods, balances and payment status from one list.</small>
                            </figcaption>
                        </figure>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="hp-preview-frame h-100">
                        <figure class="hp-preview-tile mb-0">
                            <div class="hp-preview-tile-media">
                                <img src="{{ asset('assets/images/landing-preview-properties.png') }}"
                                     alt="HOWSEY property record showing a building profile, occupancy summary and unit directory."
                                     width="1339" height="683" loading="lazy" decoding="async">
                            </div>
                            <figcaption class="hp-preview-tile-caption">
                                <strong>Property records</strong>
                                <small>Open each building profile to see occupancy, performance metrics, announcements and unit records.</small>
                            </figcaption>
                        </figure>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="hp-preview-frame h-100">
                        <figure class="hp-preview-tile mb-0">
                            <div class="hp-preview-tile-media">
                                <img src="{{ asset('assets/images/landing-preview-maintenance.png') }}"
                                     alt="HOWSEY maintenance requests view showing reported issues, priority, status and property location."
                                     width="1339" height="323" loading="lazy" decoding="async">
                            </div>
                            <figcaption class="hp-preview-tile-caption">
                                <strong>Maintenance requests</strong>
                                <small>Follow reported issues by type, priority, status and the exact unit or property involved.</small>
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 6 — How HOWSEY works --}}
    <section id="how-it-works" class="hp-section">
        <div class="container">
            <div class="text-center mb-5">
                <div class="hp-eyebrow">How it works</div>
                <h2 class="hp-title">Get your company organized in three steps.</h2>
            </div>
            <div class="row g-4">
                <div class="col-12 col-lg-4">
                    <div class="hp-step">
                        <span class="hp-step-num">1</span>
                        <h3>Create your company</h3>
                        <p class="mb-0 text-muted">Register your company and complete the available onboarding process.</p>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="hp-step">
                        <span class="hp-step-num">2</span>
                        <h3>Access your workspace</h3>
                        <p class="mb-0 text-muted">Use your company-specific workspace URL to reach your organization's HOWSEY environment. Staff and tenants register through that same workspace link.</p>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="hp-step">
                        <span class="hp-step-num">3</span>
                        <h3>Manage your operations</h3>
                        <p class="mb-0 text-muted">Start organizing properties, tenant records, rent, payments and other supported workflows.</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="{{ $companyCreateUrlMain }}" class="btn btn-custom">Create your company</a>
            </div>
        </div>
    </section>

    {{-- SECTION 7 — Who HOWSEY is for --}}
    <section id="who-its-for" class="hp-section hp-section--tint">
        <div class="container">
            <div class="text-center mb-5">
                <div class="hp-eyebrow">Who it's for</div>
                <h2 class="hp-title">Built for the people who manage properties.</h2>
            </div>
            <div class="row g-4">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="hp-card">
                        <span class="hp-icon"><i class="bi bi-house-door" aria-hidden="true"></i></span>
                        <h3>Landlords</h3>
                        <p>Maintain visibility over properties, tenants and rent records.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="hp-card">
                        <span class="hp-icon"><i class="bi bi-clipboard-check" aria-hidden="true"></i></span>
                        <h3>Property Managers</h3>
                        <p>Organize everyday property operations in one system.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="hp-card">
                        <span class="hp-icon"><i class="bi bi-diagram-3" aria-hidden="true"></i></span>
                        <h3>Estate Management Companies and Agencies</h3>
                        <p>Coordinate company records, staff responsibilities and property portfolios.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="hp-card">
                        <span class="hp-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
                        <h3>Tenants</h3>
                        <p>Access the tenant-facing features available through their company's workspace. Tenants join an existing organization and do not create their own management company.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 8 — Pricing --}}
    <section id="pricing" class="hp-section">
        <div class="container">
            <div class="text-center mb-5">
                <div class="hp-eyebrow">Pricing</div>
                <h2 class="hp-title">Find the right starting point for your company.</h2>
            </div>
            <div class="hp-pricing-card">
                <h3 class="h5 fw-bold mb-3">Start with a free workspace</h3>
                <p class="text-muted mb-2">Create your HOWSEY workspace for free and start managing your properties.</p>
                <p class="small text-muted mb-4">Pricing for any additional or future services will be published here once confirmed.</p>
                <a href="{{ $companyCreateUrlMain }}" class="btn btn-custom">Create your company</a>
                <div class="mt-3">
                    <a href="#contact" class="small text-decoration-none">Have questions? Contact us.</a>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 9 — Frequently asked questions --}}
    <section id="faqs" class="hp-section hp-section--tint">
        <div class="container">
            <div class="text-center mb-5">
                <div class="hp-eyebrow">FAQs</div>
                <h2 class="hp-title">Frequently asked questions.</h2>
            </div>
            <div class="row justify-content-center">
                <div class="col-12 col-lg-9">
                    <div class="accordion hp-accordion" id="howseyFaq">
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeading1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="false" aria-controls="faqCollapse1">What is HOWSEY?</button>
                            </h3>
                            <div id="faqCollapse1" class="accordion-collapse collapse" aria-labelledby="faqHeading1" data-bs-parent="#howseyFaq">
                                <div class="accordion-body">HOWSEY is a property management platform that helps property owners and management companies organize their operations in one workspace — buildings, units, tenants, rent, payment records, staff access and maintenance.</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeading2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">How do I create a company workspace?</button>
                            </h3>
                            <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#howseyFaq">
                                <div class="accordion-body">Choose <strong>Create your company</strong> to register your organization. HOWSEY then creates your company's own workspace with its own dedicated URL.</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeading3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">Can I manage multiple properties and units?</button>
                            </h3>
                            <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#howseyFaq">
                                <div class="accordion-body">Yes. You can keep structured records of your buildings and their individual units, and organize them as your portfolio grows.</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeading4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4">How do staff members access our workspace?</button>
                            </h3>
                            <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faqHeading4" data-bs-parent="#howseyFaq">
                                <div class="accordion-body">Staff register through your company's own workspace link and are given access based on their responsibilities.</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeading5">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse5" aria-expanded="false" aria-controls="faqCollapse5">How do tenants register or sign in?</button>
                            </h3>
                            <div id="faqCollapse5" class="accordion-collapse collapse" aria-labelledby="faqHeading5" data-bs-parent="#howseyFaq">
                                <div class="accordion-body">Tenants register through the same company workspace using its tenant registration link. Tenants join an existing organization's workspace — they do not create a separate management company.</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeading6">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse6" aria-expanded="false" aria-controls="faqCollapse6">Can I track rent and payment records?</button>
                            </h3>
                            <div id="faqCollapse6" class="accordion-collapse collapse" aria-labelledby="faqHeading6" data-bs-parent="#howseyFaq">
                                <div class="accordion-body">Yes. HOWSEY keeps rent obligations, payment records and outstanding balances organized in one place. HOWSEY maintains payment records; it does not itself process card or bank payments on your behalf.</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeading7">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse7" aria-expanded="false" aria-controls="faqCollapse7">How is company and tenant information protected?</button>
                            </h3>
                            <div id="faqCollapse7" class="accordion-collapse collapse" aria-labelledby="faqHeading7" data-bs-parent="#howseyFaq">
                                <div class="accordion-body">Access is restricted by role, and each company's records are kept within its own workspace. As with any online system, we do not claim absolute security.</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeading8">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse8" aria-expanded="false" aria-controls="faqCollapse8">How much does HOWSEY cost, and how can I get support?</button>
                            </h3>
                            <div id="faqCollapse8" class="accordion-collapse collapse" aria-labelledby="faqHeading8" data-bs-parent="#howseyFaq">
                                <div class="accordion-body">Creating your HOWSEY workspace is currently free. Support and any future pricing details will be published here once confirmed.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 10 — Contact (placeholder only; no form until backend is approved) --}}
    <section id="contact" class="hp-section">
        <div class="container">
            <div class="text-center mb-5">
                <div class="hp-eyebrow">Contact</div>
                <h2 class="hp-title">Have questions about HOWSEY?</h2>
                <p class="hp-sub">Tell us a little about your property management needs, and our team can respond with relevant information.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">
                    <div class="hp-card text-center">
                        <span class="hp-icon"><i class="bi bi-envelope-paper" aria-hidden="true"></i></span>
                        <h3>Email us</h3>
                        <p class="mb-3">Email is our current contact channel. Send your questions to the address below and we will respond with the information you need. There is no enquiry form yet.</p>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="mailto:howsey455@gmail.com" class="btn btn-custom">
                                <i class="bi bi-envelope me-1" aria-hidden="true"></i> howsey455@gmail.com
                            </a>
                            <a href="https://instagram.com/howsey.site" target="_blank" rel="noopener noreferrer" class="btn btn-custom">
                                <i class="bi bi-instagram me-1" aria-hidden="true"></i> Follow HOWSEY on Instagram
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 11 — Final conversion banner --}}
    <section class="hp-section pt-0">
        <div class="container">
            <div class="hp-cta">
                <h2>Ready to bring your property management together?</h2>
                <p class="mb-4">Create your company's HOWSEY workspace and start organizing your property operations.</p>
                <a href="{{ $companyCreateUrlMain }}" class="btn btn-custom btn-lg">Create your company</a>
                <div class="mt-3">
                    <a href="#contact" class="text-white">Have questions? Contact us.</a>
                </div>
            </div>
        </div>
    </section>
    @endif
    </main>

    <div class="modal fade" id="getStartedModal" tabindex="-1" aria-labelledby="getStartedModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title" id="getStartedModalLabel">Choose your role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        {{ $portalCompany ? 'Select the registration path that matches how this user will use the '.$brandName.' workspace.' : 'Start by creating your company profile, then use the generated company URL to register staff and tenants.' }}
                    </p>
                    <div class="d-grid gap-3">
                        @if($portalCompany)
                            <a href="{{ $staffRegisterUrl }}" class="role-option-card p-3">
                                <div class="fw-bold text-primary mb-1">Manager or Accountant</div>
                                <div class="small text-muted">Create a staff account tied directly to the {{ $brandName }} workspace.</div>
                            </a>
                            <a href="{{ $tenantRegisterUrl }}" class="role-option-card p-3">
                                <div class="fw-bold text-primary mb-1">Tenant</div>
                                <div class="small text-muted">Create a tenant account inside this same company workspace.</div>
                            </a>
                            <a href="{{ $portalLoginUrl }}" class="role-option-card p-3">
                                <div class="fw-bold text-primary mb-1">Already have an account?</div>
                                <div class="small text-muted">Go to the company login page and sign in under {{ $brandName }}.</div>
                            </a>
                        @else
                            <a href="{{ $companyCreateUrl }}" class="role-option-card p-3">
                                <div class="fw-bold text-primary mb-1">Create Company</div>
                                <div class="small text-muted">Set up your company profile, logo, first admin, and company-specific workspace URL.</div>
                            </a>
                            <a href="{{ $existingWorkspaceAdminLoginUrl }}" class="role-option-card p-3">
                                <div class="fw-bold text-primary mb-1">Existing Workspace Access</div>
                                <div class="small text-muted">Go to the workspace login page to sign in with your account ID and password.</div>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($isMainHowseyHome)
        {{-- SECTION 12 — Professional business footer (public homepage only) --}}
        <footer class="hp-footer">
            <div class="container">
                <div class="row g-4">
                    <div class="col-12 col-lg-4">
                        <a href="/" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-3">
                            <img src="{{ url('/howsey.png') }}" alt="HOWSEY" width="28" height="30" loading="lazy" decoding="async" style="object-fit:contain;">
                            <span class="hp-footer-brand">HOWSEY</span>
                        </a>
                        <p class="mb-3 small">Property management software that helps owners and management companies organize properties, tenants, rent and payment records in one workspace.</p>
                        <a href="https://instagram.com/howsey.site" target="_blank" rel="noopener noreferrer" aria-label="Follow HOWSEY on Instagram">
                            <i class="bi bi-instagram fs-5" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="col-6 col-lg-2">
                        <h2>Product</h2>
                        <ul class="list-unstyled mb-0 d-grid gap-2">
                            <li><a href="#features">Features</a></li>
                            <li><a href="#how-it-works">How It Works</a></li>
                            <li><a href="#pricing">Pricing</a></li>
                            <li><a href="{{ $companyCreateUrlMain }}">Create Your Company</a></li>
                            <li><a href="{{ $adminLoginUrlMain }}">Sign In</a></li>
                        </ul>
                    </div>
                    <div class="col-6 col-lg-2">
                        <h2>Company</h2>
                        <ul class="list-unstyled mb-0 d-grid gap-2">
                            <li><a href="#contact">Contact</a></li>
                            <li><a href="#preview">Product Preview</a></li>
                            <li><a href="#faqs">FAQs</a></li>
                        </ul>
                    </div>
                    <div class="col-12 col-lg-4">
                        <h2>Legal</h2>
                        <ul class="list-unstyled mb-0 d-grid gap-2">
                            <li><a href="{{ route('legal.privacy') }}">Privacy Policy</a></li>
                            <li><a href="{{ route('legal.terms') }}">Terms of Service</a></li>
                        </ul>
                    </div>
                </div>
                <div class="hp-footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2">
                    <span>&copy; 2026 HOWSEY. All rights reserved.</span>
                    <span class="text-white-50">Built for property owners and management teams.</span>
                </div>
            </div>
        </footer>
    @else
        <footer class="text-center py-3 bg-white border-top">
            <div class="container">
                <p class="mb-0 text-muted small">
                    produced by Noyb Fundamentals 2026 &copy;
                </p>
            </div>
        </footer>
    @endif

    @if($enablePortalPwaUi)
        <div class="portal-pwa-actions">
            <button id="portal-pwa-install-button" type="button" class="btn btn-primary">
                <i class="bi bi-download"></i>
                <span>Install App</span>
            </button>
        </div>

        <div class="modal fade" id="portalPwaInstallModal" tabindex="-1" aria-labelledby="portalPwaInstallModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header">
                        <h5 class="modal-title" id="portalPwaInstallModalLabel">Install {{ $brandName }} App</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Install the {{ $brandName }} workspace to keep it handy on desktop or mobile.</p>
                        <div class="d-none d-md-block">
                            <ol class="ps-3 mb-0">
                                <li class="mb-2">Click the install icon in the address bar.</li>
                                <li>Or open the browser menu and choose <strong>Install App</strong>.</li>
                            </ol>
                        </div>
                        <div class="d-block d-md-none">
                            <div class="mb-3">
                                <p class="mb-2 fw-bold text-dark"><i class="bi bi-android2 text-success me-1"></i> Android</p>
                                <ol class="ps-3 mb-0 small text-dark">
                                    <li class="mb-1">Open the browser menu.</li>
                                    <li>Tap <strong>Install App</strong> or <strong>Add to Home Screen</strong>.</li>
                                </ol>
                            </div>
                            <div>
                                <p class="mb-2 fw-bold text-dark"><i class="bi bi-apple text-dark me-1"></i> iPhone / iPad</p>
                                <ol class="ps-3 mb-0 small text-dark">
                                    <li class="mb-1">Tap the <strong>Share</strong> button.</li>
                                    <li>Choose <strong>Add to Home Screen</strong>.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
            new bootstrap.Tooltip(element);
        });
    </script>
    @if($isMainHowseyHome)
        <script>
            (function () {
                const loginUrl = @json($portalLoginUrl);
                let awaitingSecretKey = false;
                let prefixTimer = null;

                function clearSecretPrefix() {
                    awaitingSecretKey = false;
                    if (prefixTimer) {
                        window.clearTimeout(prefixTimer);
                        prefixTimer = null;
                    }
                }

                function openHowseyLogin() {
                    window.location.href = loginUrl;
                }

                window.addEventListener('keydown', function (event) {
                    const target = event.target;
                    const isTypingTarget = target instanceof HTMLElement && (
                        target.isContentEditable ||
                        ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)
                    );

                    if (isTypingTarget || event.repeat) {
                        return;
                    }

                    const key = String(event.key || '').toLowerCase();

                    if ((event.ctrlKey || event.metaKey) && !event.shiftKey && !event.altKey && key === 'l') {
                        event.preventDefault();
                        event.stopPropagation();
                        openHowseyLogin();
                        clearSecretPrefix();
                        return;
                    }

                    if (!event.ctrlKey && !event.metaKey && !event.altKey && !event.shiftKey && key === 'g') {
                        awaitingSecretKey = true;
                        if (prefixTimer) {
                            window.clearTimeout(prefixTimer);
                        }
                        prefixTimer = window.setTimeout(clearSecretPrefix, 1200);
                        event.preventDefault();
                        return;
                    }

                    if (awaitingSecretKey && !event.ctrlKey && !event.metaKey && !event.altKey && !event.shiftKey && key === 'l') {
                        event.preventDefault();
                        event.stopPropagation();
                        openHowseyLogin();
                        clearSecretPrefix();
                        return;
                    }

                    if (awaitingSecretKey) {
                        clearSecretPrefix();
                    }
                }, true);
            })();
        </script>
        <script>
            // Public homepage only: collapse the mobile menu once a section link is chosen
            // so the anchor target is visible straight away on small screens.
            (function () {
                const nav = document.getElementById('navbarNav');

                if (!nav) {
                    return;
                }

                nav.querySelectorAll('a[href^="#"]').forEach(function (link) {
                    link.addEventListener('click', function () {
                        if (!nav.classList.contains('show') || typeof bootstrap === 'undefined' || !bootstrap.Collapse) {
                            return;
                        }

                        bootstrap.Collapse.getOrCreateInstance(nav, { toggle: false }).hide();
                    });
                });
            })();
        </script>
    @elseif($enablePortalPwaUi)
        <script>
            (function () {
                const installButton = document.getElementById('portal-pwa-install-button');
                const installModal = document.getElementById('portalPwaInstallModal');
                const serviceWorkerUrl = @json(url('/service-worker.js'));
                let deferredInstallPrompt = null;

                if (!('serviceWorker' in navigator)) {
                    return;
                }

                window.addEventListener('load', function () {
                    navigator.serviceWorker.register(serviceWorkerUrl).catch(function (error) {
                        console.warn('Portal service worker registration failed', error);
                    });
                }, { once: true });

                if (!installButton) {
                    return;
                }

                window.addEventListener('beforeinstallprompt', function (event) {
                    event.preventDefault();
                    deferredInstallPrompt = event;
                    installButton.style.display = 'inline-flex';
                });

                window.addEventListener('appinstalled', function () {
                    deferredInstallPrompt = null;
                    installButton.style.display = 'none';
                });

                installButton.addEventListener('click', async function () {
                    if (deferredInstallPrompt) {
                        deferredInstallPrompt.prompt();
                        try {
                            await deferredInstallPrompt.userChoice;
                        } finally {
                            deferredInstallPrompt = null;
                        }
                        return;
                    }

                    if (installModal && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        bootstrap.Modal.getOrCreateInstance(installModal).show();
                        return;
                    }

                    window.alert('To install this app, use your browser menu and choose Install App or Add to Home Screen.');
                });
            })();
        </script>
    @endif

    @include('partials.howsey-credit')
</body>
</html>
