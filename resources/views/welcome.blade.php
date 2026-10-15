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
        $existingWorkspaceAdminLoginUrl = route('login', ['entry' => 'company-admin']);
        $isMainHowseyHome = ! $portalCompany;
        $enablePortalPwaUi = (bool) $portalCompany;
        $companyHasUploadedLogo = filled(optional($portalCompany?->settings)->logo_path);
        $portalQrUrl = $portalCompany
            ? 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='.rawurlencode(route('companies.portal', $portalCompany->slug))
            : null;
        $pageTitle = $portalCompany ? $brandName.' Workspace' : config('app.name', 'HOWSEY');
        $heroTitle = $portalCompany ? 'Welcome to '.$brandName : 'Modern Property Management Simplified';
        $heroSubtitle = $portalCompany
            ? 'Welcome to your company workspace. Register staff, onboard tenants, and manage everything from one simple HOWSEY experience across desktop and mobile.'
            : 'Streamline your rental operations, manage tenants efficiently, and track payments effortlessly with HOWSEY.';
        $getStartedHelp = $portalCompany
            ? 'Use Get Started to register new managers, accountants, or tenants directly into the '.$brandName.' workspace.'
            : 'Create your company profile from here, then share the generated company URL with your staff for company-specific registration and sign-in.';
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
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
            bottom: 1rem;
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
                bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px));
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
    </style>
</head>
<body>
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
                                    <a href="https://instagram.com/howsey.site?cplk=MWFhbGV2dDJzdWNjdw%3D%3D&utm_source=qr" target="_blank" rel="noopener noreferrer"
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
            @if($portalCompany)
                <div class="portal-links-card-mobile d-md-none mt-4 bg-white bg-opacity-10 border border-light border-opacity-25 rounded-4 p-3 text-start">
                    <div class="text-center">
                        <div class="small text-white-50 text-uppercase fw-semibold mb-2">Scan Company Homepage</div>
                        <img src="{{ $portalQrUrl }}" alt="{{ $brandName }} workspace QR code" class="portal-qr-image img-fluid bg-white rounded-3 p-2">
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
                    <img src="{{ $portalQrUrl }}" alt="{{ $brandName }} workspace QR code" class="portal-qr-image img-fluid bg-white rounded-3 p-2">
                    <div class="small text-white-50 mt-2">Scan to open {{ $brandName }} on this device.</div>
                </div>
            </div>
        @endif
    </div>
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
                                <div class="small text-muted">Go to the company-admin login page. Only company admins can sign in or request password recovery from the main HOWSEY homepage.</div>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-3 bg-white border-top">
        <div class="container">
            <p class="mb-0 text-muted small">
                produced by Noyb Fundamentals 2026 &copy;
            </p>
        </div>
    </footer>

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
</body>
</html>
