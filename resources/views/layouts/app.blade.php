<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $branding['app_name'] ?? config('app.name', 'HOWSEY') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nunito:wght@400;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    @php
        $brandDisplayName = $branding['app_name'] ?? config('app.name', 'HOWSEY');
        $brandLogoUrl = $branding['logo_url'] ?? null;
        $brandPrimary = $branding['primary_color'] ?? '#7c3aed';
        $brandPrimaryHover = $branding['primary_hover'] ?? '#6d28d9';
        $brandPrimaryText = $branding['primary_text_color'] ?? '#ffffff';
        $brandPrimaryTextRgb = $branding['primary_text_rgb'] ?? '255, 255, 255';
        $brandPrimaryTextMuted = $branding['primary_text_muted'] ?? 'rgba(255, 255, 255, 0.82)';
        $brandPrimaryHex = ltrim($brandPrimary, '#');
        $brandPrimaryRgb = strlen($brandPrimaryHex) === 6
            ? implode(', ', array_map('hexdec', str_split($brandPrimaryHex, 2)))
            : '124, 58, 237';
        $usesRoleWatermark = Auth::check() && !Auth::user()->isAdmin() && (Auth::user()->isAccountant() || Auth::user()->isManager());
        $roleAccountIdentity = $usesRoleWatermark
            ? \App\Support\AccountIdentityService::accountIdWithTimestamp(Auth::user())
            : null;
        $roleWatermarkText = $usesRoleWatermark
            ? \App\Support\AccountIdentityService::watermarkTextFor(Auth::user())
            : '';
        $roleWatermarkTile = $usesRoleWatermark
            ? \App\Support\AccountIdentityService::watermarkTileUrl($roleWatermarkText)
            : '';
        $superAdminViewCompanies = $superAdminViewCompanies ?? collect();
        $showSuperAdminCompanySwitcher = Auth::check()
            && Auth::user()->isSuperAdmin()
            && $superAdminViewCompanies->isNotEmpty();
        $superAdminViewingCompanyName = optional($superAdminViewingCompany)->name ?: 'No company selected';
        $superAdminViewingCompanyLabel = $showSuperAdminCompanySwitcher
            ? 'Viewing Company: '.$superAdminViewingCompanyName
            : null;
        $enableChatbot = Auth::check() && request()->routeIs('dashboard');
        $enableScreensaver = $enableChatbot;
        $enablePwaUi = true;
        $enablePwaInstallUi = $enablePwaUi;
        $enablePwaNotificationUi = $enablePwaUi && Auth::check();
        $shellAssetVersion = '20261026-components';
        $shortcutRoutes = Auth::check()
            ? [
                'home' => url('/'),
                'dashboard' => Auth::user()->isAccountant() ? route('accounting.index') : route('dashboard'),
                'analytics' => (Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) ? route('dashboard.analytics') : null,
                'buildings' => ((Auth::user()->isAdmin() || Auth::user()->isManager()) && !Auth::user()->isSuperAdmin()) ? route('buildings.index') : null,
                'tenants' => ((Auth::user()->isAdmin() || Auth::user()->isManager()) && !Auth::user()->isSuperAdmin()) ? route('tenants.index') : null,
                'rentals' => ((Auth::user()->isAdmin() || Auth::user()->isManager()) && !Auth::user()->isSuperAdmin()) ? route('rents.index') : null,
                'payments' => ((Auth::user()->isAdmin() || Auth::user()->isManager()) && !Auth::user()->isSuperAdmin()) ? route('payments.index') : null,
                'maintenance' => ((Auth::user()->isTenant() || Auth::user()->isAdmin() || Auth::user()->isManager()) && !Auth::user()->isSuperAdmin()) ? route('maintenance.index') : null,
                'accounting' => ((Auth::user()->isAdmin() || Auth::user()->isAccountant()) && !Auth::user()->isSuperAdmin()) ? route('accounting.index') : null,
            ]
            : [
                'home' => url('/'),
            ];
        $shortcutDefinitions = collect([
            ['combo' => ['Alt', 'Shift', 'H'], 'label' => 'Home', 'description' => 'Go to the home page. Also works with G then H.', 'enabled' => !empty($shortcutRoutes['home'])],
            ['combo' => ['Alt', 'Shift', 'D'], 'label' => 'Dashboard', 'description' => 'Open your main workspace. Also works with G then D.', 'enabled' => !empty($shortcutRoutes['dashboard'])],
            ['combo' => ['Alt', 'Shift', 'A'], 'label' => 'Analytics', 'description' => 'Open dashboard analytics. Also works with G then A.', 'enabled' => !empty($shortcutRoutes['analytics'])],
            ['combo' => ['Alt', 'Shift', 'B'], 'label' => 'Buildings', 'description' => 'Open buildings. Also works with G then B.', 'enabled' => !empty($shortcutRoutes['buildings'])],
            ['combo' => ['Alt', 'Shift', 'T'], 'label' => 'Tenants', 'description' => 'Open tenants. Also works with G then T.', 'enabled' => !empty($shortcutRoutes['tenants'])],
            ['combo' => ['Alt', 'Shift', 'R'], 'label' => 'Rentals', 'description' => 'Open rentals. Also works with G then R.', 'enabled' => !empty($shortcutRoutes['rentals'])],
            ['combo' => ['Alt', 'Shift', 'P'], 'label' => 'Payments', 'description' => 'Open payments. Also works with G then P.', 'enabled' => !empty($shortcutRoutes['payments'])],
            ['combo' => ['Alt', 'Shift', 'M'], 'label' => 'Maintenance', 'description' => 'Open maintenance. Also works with G then M.', 'enabled' => !empty($shortcutRoutes['maintenance'])],
            ['combo' => ['Alt', 'Shift', 'C'], 'label' => 'Accounting', 'description' => 'Open accounting. Also works with G then C.', 'enabled' => !empty($shortcutRoutes['accounting'])],
            ['combo' => ['Alt', 'Shift', 'L'], 'label' => 'Logout', 'description' => 'Sign out of the current account. Also works with G then L.', 'enabled' => Auth::check()],
            ['combo' => ['G', 'S'], 'label' => 'Screensaver', 'description' => 'Start screensaver mode. Move the mouse, tap, scroll, or press any key to return.', 'enabled' => $enableScreensaver],
            ['combo' => ['Alt', 'Left'], 'label' => 'History Back', 'description' => 'Press Alt + Left Arrow to go to the previous page in your workspace history.', 'enabled' => Auth::check()],
            ['combo' => ['Alt', 'Right'], 'label' => 'History Forward', 'description' => 'Press Alt + Right Arrow to go to the next page in your workspace history.', 'enabled' => Auth::check()],
            ['combo' => ['/'], 'label' => 'Search', 'description' => 'Focus the visible page search box', 'enabled' => true],
            ['combo' => ['Esc'], 'label' => 'Close', 'description' => 'Close an open modal or the sidebar', 'enabled' => true],
            ['combo' => ['Alt', 'Shift', '?'], 'label' => 'Shortcut Help', 'description' => 'Open this keyboard shortcuts panel. Also works with G then /.', 'enabled' => true],
        ])->filter(fn ($shortcut) => $shortcut['enabled'])->values();
    @endphp

    @if($enablePwaUi)
        <link rel="manifest" href="{{ $branding['pwa_manifest_url'] ?? url('/manifest.webmanifest') }}">
        <meta name="theme-color" content="{{ $branding['primary_color'] ?? '#7c3aed' }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <link rel="icon" href="{{ $branding['pwa_icon_url'] ?? url('/howsey.png') }}" type="image/png">
        <link rel="apple-touch-icon" sizes="512x512" href="{{ $branding['pwa_icon_url'] ?? url('/howsey.png') }}">
    @endif

    @if(request()->routeIs('login') || request()->routeIs('companies.login'))
        <link rel="preload" href="{{ asset('assets/images/wallpaper.jpeg') }}" as="image" fetchpriority="high">
    @endif

    <link rel="preload" href="{{ asset('assets/css/app-shell.css') }}?v={{ $shellAssetVersion }}" as="style">
    <link rel="stylesheet" href="{{ asset('assets/css/app-shell.css') }}?v={{ $shellAssetVersion }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components.css') }}?v={{ $shellAssetVersion }}">
    <style>
        :root {
            --primary-color: {{ $brandPrimary }};
            --primary-hover: {{ $brandPrimaryHover }};
            --brand-primary-rgb: {{ $brandPrimaryRgb }};
            --on-primary: {{ $brandPrimaryText }};
            --on-primary-rgb: {{ $brandPrimaryTextRgb }};
            --on-primary-muted: {{ $brandPrimaryTextMuted }};
            --shell-header-bg: rgba(var(--brand-primary-rgb), 0.12);
            --shell-main-bg: rgba(var(--brand-primary-rgb), 0.06);
            --shell-sidebar-bg: rgba(var(--brand-primary-rgb), 0.82);
            --shell-sidebar-header-bg: rgba(0, 0, 0, 0.16);
            --shell-sidebar-link: rgba(255, 255, 255, 0.88);
            --shell-sidebar-link-active-bg: rgba(255, 255, 255, 0.18);
            --shell-card-bg: rgba(255, 255, 255, 0.92);
            --shell-hover-bg: rgba(var(--brand-primary-rgb), 0.08);
        }
        /* Shell CSS moved to public/assets/css/app-shell.css (Phase 2). */
    </style>
    <script>
        document.documentElement.classList.add('app-shell-loading');
        window.addEventListener('load', function () {
            document.documentElement.classList.remove('app-shell-loading');
        });
    </script>
</head>
<body class="{{ $usesRoleWatermark ? 'role-watermark-enabled' : '' }}">
    <!-- Premium Loader HTML -->
    <div id="howsey-loader-overlay" class="howsey-loader-overlay" role="status" aria-live="polite" aria-busy="false">
        <div class="loader"></div>
        <div class="text-center">
            <div class="fw-bold brand-title text-primary">{{ $brandDisplayName }}</div>
            <div class="small text-muted">Loading your workspace...</div>
        </div>
        <span class="visually-hidden">Loading...</span>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <div class="container-fluid p-0">
        @auth
        <div class="mobile-header app-sticky-header d-lg-none d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <button class="sidebar-toggle" id="sidebarToggleBtn" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <div class="ms-3 fw-bold text-primary fs-5">
                    <a href="{{ $branding['company_portal_url'] ?? url('/') }}" class="text-decoration-none text-primary d-flex align-items-center gap-2">
                        @if($brandLogoUrl)
                            <img src="{{ $brandLogoUrl }}" alt="{{ $brandDisplayName }} logo" class="brand-logo">
                        @else
                            <i class="bi bi-building"></i>
                        @endif
                        <span class="brand-title">{{ $brandDisplayName }}</span>
                    </a>
                </div>
            </div>
        </div>
        @if($showSuperAdminCompanySwitcher)
            <div class="d-lg-none px-3 pt-2 pb-2 border-bottom bg-white">
                <div class="small text-muted fw-semibold mb-1">Current workspace</div>
                <form method="POST" action="{{ route('super-admin.view-company') }}" class="workspace-switcher">
                    @csrf
                    <select name="company_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($superAdminViewCompanies as $viewCompanyOption)
                            <option value="{{ $viewCompanyOption->id }}" {{ (int) $superAdminViewingCompanyId === (int) $viewCompanyOption->id ? 'selected' : '' }}>
                                {{ $viewCompanyOption->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        @endif
        @endauth

        <div class="row g-0">
            <!-- Sidebar -->
            @auth
            <div class="sidebar" id="sidebarMenu">
                <div class="nav-header">
                    <span class="d-flex align-items-center gap-2">
                        @if($brandLogoUrl)
                            <img src="{{ $brandLogoUrl }}" alt="{{ $brandDisplayName }} logo" class="brand-logo">
                        @else
                            <i class="bi bi-building"></i>
                        @endif
                        <span class="brand-title">{{ $brandDisplayName }}</span>
                    </span>
                    <button class="btn btn-sm text-white" onclick="toggleSidebar()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="pt-2 sidebar-content">
                    @php
                        $primaryWorkspaceRoute = Auth::user()->isAccountant() ? route('accounting.index') : route('dashboard');
                        $primaryWorkspaceLabel = Auth::user()->isAccountant() ? 'Accounting' : 'Dashboard';
                        $homePortalUrl = $branding['company_portal_url'] ?? url('/');
                    @endphp
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ $homePortalUrl }}">
                                <i class="bi bi-house me-2"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') || request()->routeIs('accounting.*') ? 'active' : '' }}" href="{{ $primaryWorkspaceRoute }}">
                                <i class="bi bi-speedometer2 me-2"></i> {{ $primaryWorkspaceLabel }}
                            </a>
                        </li>
                        @can('viewAny', \App\Models\Company::class)
                            <li class="nav-item px-3 mt-2 mb-1">
                                <a class="btn btn-sm btn-light w-100 text-start fw-semibold d-flex align-items-center justify-content-between" href="{{ route('companies.index') }}">
                                    <span><i class="bi bi-buildings me-2"></i> Companies</span>
                                    <i class="bi bi-arrow-right-short"></i>
                                </a>
                            </li>
                            @if(Auth::user()->isSuperAdmin())
                                <li class="nav-item px-3 mb-2">
                                    <a class="btn btn-sm btn-outline-light w-100 text-start fw-semibold d-flex align-items-center justify-content-between" href="{{ route('company-applications.index') }}">
                                        <span><i class="bi bi-credit-card-2-front me-2"></i> Applications</span>
                                        <i class="bi bi-arrow-right-short"></i>
                                    </a>
                                </li>
                            @endif
                        @endcan
                        @if($showSuperAdminCompanySwitcher)
                            <li class="nav-item px-3 mb-2">
                                <div class="small text-uppercase text-white-50 fw-semibold mb-1">Current Workspace</div>
                                <form method="POST" action="{{ route('super-admin.view-company') }}">
                                    @csrf
                                    <select name="company_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                        @foreach($superAdminViewCompanies as $viewCompanyOption)
                                            <option value="{{ $viewCompanyOption->id }}" {{ (int) $superAdminViewingCompanyId === (int) $viewCompanyOption->id ? 'selected' : '' }}>
                                                {{ $viewCompanyOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </li>
                        @endif
                        @if(!Auth::user()->isSuperAdmin())
                        @permission('buildings.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('buildings.*') ? 'active' : '' }}" href="{{ route('buildings.index') }}">
                                <i class="bi bi-houses me-2"></i> Buildings
                            </a>
                        </li>
                        @endpermission
                        @permission('tenants.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenants.*') ? 'active' : '' }}" href="{{ route('tenants.index') }}">
                                <i class="bi bi-people me-2"></i> Tenants
                            </a>
                        </li>
                        @endpermission
                        @permission('rentals.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('rents.*') ? 'active' : '' }}" href="{{ route('rents.index') }}">
                                <i class="bi bi-file-earmark-text me-2"></i> Rentals
                            </a>
                        </li>
                        @endpermission
                        @endif

                        @if(!Auth::user()->isSuperAdmin())
                        @permission('payments.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">
                                <i class="bi bi-cash-stack me-2"></i> Payments
                            </a>
                        </li>
                        @endpermission
                        @endif

                        @if(!Auth::user()->isSuperAdmin() && (Auth::user()->isAdmin() || Auth::user()->isManager()) && Route::has('payment-claims.index'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('payment-claims.*') ? 'active' : '' }}" href="{{ route('payment-claims.index') }}">
                                <i class="bi bi-receipt-cutoff me-2"></i> Payment Claims
                            </a>
                        </li>
                        @endif

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('messages.*') ? 'active' : '' }}" href="{{ route('messages.index') }}">
                                <i class="bi bi-chat-dots me-2"></i> Messages
                            </a>
                        </li>

                        @if(!Auth::user()->isSuperAdmin() && !Auth::user()->isTenant())
                        @permission('maintenance.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('maintenance.*') ? 'active' : '' }}" href="{{ route('maintenance.index') }}">
                                <i class="bi bi-tools me-2"></i> Maintenance
                            </a>
                        </li>
                        @endpermission
                        @endif
                        @if(!Auth::user()->isSuperAdmin())
                        @permission('users.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                <i class="bi bi-person-gear me-2"></i> Users
                            </a>
                        </li>
                        @endpermission
                        @endif

                        @permission('roles.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                                <i class="bi bi-shield-lock me-2"></i> Roles
                            </a>
                        </li>
                        @endpermission
                        @if(Auth::user()->company_id && Auth::user()->hasPermission('settings.view'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('company-settings.*') ? 'active' : '' }}" href="{{ route('company-settings.edit') }}">
                                    <i class="bi bi-palette me-2"></i> Company Settings
                                </a>
                            </li>
                        @endif
                        @if((Auth::user()->isAdmin() || Auth::user()->isManager()) && Route::has('password-recovery-requests.index'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('password-recovery-requests.*') ? 'active' : '' }}" href="{{ route('password-recovery-requests.index') }}">
                                    <i class="bi bi-key me-2"></i> Password Recovery
                                </a>
                            </li>
                        @endif
                        @if(!Auth::user()->isSuperAdmin() && (Auth::user()->isAdmin() || Auth::user()->isManager()) && Route::has('manager-edit-requests.index'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('manager-edit-requests.*') ? 'active' : '' }}" href="{{ route('manager-edit-requests.index') }}">
                                    <i class="bi bi-pencil-square me-2"></i> Edit Requests
                                </a>
                            </li>
                        @endif

                        @if(!Auth::user()->isSuperAdmin() && (Auth::user()->isAdmin() || Auth::user()->isAccountant()))
                        @permission('accounting.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('accounting.*') ? 'active' : '' }}" href="{{ route('accounting.index') }}">
                                <i class="bi bi-calculator me-2"></i> Accounting
                            </a>
                        </li>
                        @endpermission
                        @endif

                        @if(Auth::user()->isTenant())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('maintenance.*') ? 'active' : '' }}" href="{{ route('maintenance.index') }}">
                                <i class="bi bi-tools me-2"></i> Request Maintenance
                            </a>
                        </li>
                        @endif
                    </ul>

                    <hr class="text-white">
                    <div class="px-3 pb-3 sidebar-user">
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-shrink-0">
                                <i class="bi bi-person-circle fs-4"></i>
                            </div>
                            <div class="flex-grow-1 ms-2 overflow-hidden">
                                <div class="small fw-bold text-truncate">{{ Auth::user()->name }}</div>
                                <div class="small text-muted text-truncate">{{ Auth::user()->email }}</div>
                                @if($roleAccountIdentity)
                                    <div class="small text-muted text-truncate">{{ $roleAccountIdentity }}</div>
                                @endif
                            </div>
                        </div>
                        @if(Route::has('account.edit'))
                            <a href="{{ route('account.edit') }}" class="btn btn-outline-light btn-sm w-100 mb-2">
                                <i class="bi bi-gear me-1"></i> Account Settings
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" id="keyboard-logout-form">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm w-100">
                                <i class="bi bi-box-arrow-right me-1"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endauth

            <!-- Main Content -->
            <main class="@auth main-content @else col-12 @endauth {{ $usesRoleWatermark ? 'role-watermarked' : '' }}" @if($usesRoleWatermark) data-watermark="{{ $roleWatermarkText }}" style="--wm-tile: {{ $roleWatermarkTile }}" @endif>
                @auth
                <!-- Desktop Collapsed Header -->
                <div id="desktopHeader" class="app-sticky-header d-none d-lg-flex align-items-center mb-0 pb-2 border-bottom">
                    <button class="sidebar-toggle me-3" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                    <span class="h4 mb-0 text-primary fw-bold d-flex align-items-center gap-2">
                        @if($brandLogoUrl)
                            <img src="{{ $brandLogoUrl }}" alt="{{ $brandDisplayName }} logo" class="brand-logo">
                        @else
                            <i class="bi bi-building"></i>
                        @endif
                        <span class="brand-title">{{ $brandDisplayName }}</span>
                    </span>

                    @if($showSuperAdminCompanySwitcher)
                        <div class="ms-3 d-flex align-items-center gap-2 flex-wrap">
                            <span class="workspace-chip">
                                <i class="bi bi-buildings"></i> {{ $superAdminViewingCompanyLabel }}
                            </span>
                            <form method="POST" action="{{ route('super-admin.view-company') }}" class="workspace-switcher">
                                @csrf
                                <select name="company_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    @foreach($superAdminViewCompanies as $viewCompanyOption)
                                        <option value="{{ $viewCompanyOption->id }}" {{ (int) $superAdminViewingCompanyId === (int) $viewCompanyOption->id ? 'selected' : '' }}>
                                            {{ $viewCompanyOption->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    @endif

                    <div class="ms-auto me-3 d-flex align-items-center gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#keyboardShortcutsModal"
                            title="Keyboard shortcuts"
                            aria-label="Keyboard shortcuts"
                        >
                            <i class="bi bi-keyboard me-1"></i> Shortcuts
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell"></i>
                                @if(($globalNotificationCount ?? 0) > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $globalNotificationCount }}</span>
                                @endif
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-0 notification-dropdown-menu">
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                    <span class="fw-semibold">Notifications</span>
                                    @if(($globalNotificationCount ?? 0) > 0)
                                        <form method="POST" action="{{ route('notifications.read-all') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-sm text-decoration-none p-0">Mark all as read</button>
                                        </form>
                                    @endif
                                </div>
                                @forelse(($globalUnreadNotifications ?? collect()) as $notification)
                                    <a href="{{ route('notifications.open', $notification->id) }}" class="dropdown-item px-3 py-2">
                                        <div class="fw-semibold">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                        <div class="small text-muted notification-message">{{ $notification->data['message'] ?? 'A new item needs your attention.' }}</div>
                                        <div class="small text-muted mt-1">{{ $notification->created_at?->diffForHumans() }}</div>
                                    </a>
                                @empty
                                    <div class="px-3 py-3 text-muted small">No unread notifications.</div>
                                @endforelse
                            </div>
                        </div>

                        @if(isset($globalAnnouncementCount) && $globalAnnouncementCount > 0)
                            <a
                                id="announcement-badge-desktop"
                                href="{{ $globalAnnouncementUrl ?? route('dashboard') }}"
                                class="badge rounded-pill bg-danger announcement-badge text-decoration-none"
                                title="Open announcements"
                                aria-label="Open announcements"
                            >
                                <i class="bi bi-megaphone-fill me-1"></i>{{ $globalAnnouncementCount }}
                            </a>
                        @endif
                    </div>

                </div>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom d-md-none">
                    <h1 class="h2 text-primary fw-bold">{{ Auth::user()->isAdmin() ? 'Admin' : (Auth::user()->isAccountant() ? 'Accounting' : (Auth::user()->isManager() ? 'Manager' : 'Tenant')) }}</h1>

                    <div class="d-flex align-items-center ms-auto gap-2">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell"></i>
                                @if(($globalNotificationCount ?? 0) > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $globalNotificationCount }}</span>
                                @endif
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-0 notification-dropdown-menu">
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                    <span class="fw-semibold">Notifications</span>
                                    @if(($globalNotificationCount ?? 0) > 0)
                                        <form method="POST" action="{{ route('notifications.read-all') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-sm text-decoration-none p-0">Mark all as read</button>
                                        </form>
                                    @endif
                                </div>
                                @forelse(($globalUnreadNotifications ?? collect()) as $notification)
                                    <a href="{{ route('notifications.open', $notification->id) }}" class="dropdown-item px-3 py-2">
                                        <div class="fw-semibold">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                        <div class="small text-muted notification-message">{{ $notification->data['message'] ?? 'A new item needs your attention.' }}</div>
                                        <div class="small text-muted mt-1">{{ $notification->created_at?->diffForHumans() }}</div>
                                    </a>
                                @empty
                                    <div class="px-3 py-3 text-muted small">No unread notifications.</div>
                                @endforelse
                            </div>
                        </div>
                        @if(isset($globalAnnouncementCount) && $globalAnnouncementCount > 0)
                            <a
                                id="announcement-badge-mobile"
                                href="{{ $globalAnnouncementUrl ?? route('dashboard') }}"
                                class="badge rounded-pill bg-danger me-2 announcement-badge text-decoration-none"
                                title="Open announcements"
                                aria-label="Open announcements"
                            >
                                <i class="bi bi-megaphone-fill me-1"></i>{{ $globalAnnouncementCount }}
                            </a>
                        @endif
                    </div>
                </div>
                @endauth

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @auth
                    @if(!request()->routeIs('dashboard') && !auth()->user()->isAccountant())
                        <div class="mb-2">
                            <a href="javascript:history.back()" class="text-decoration-none text-secondary">
                                <i class="bi bi-arrow-left fs-5"></i>
                            </a>
                        </div>
                    @endif
                @endauth

                @yield('content')
            </main>
        </div>
    </div>

    <footer class="text-center py-3 bg-white border-top mt-auto" style="position: relative; z-index: 10;">
        <div class="container d-flex justify-content-center align-items-center gap-2">
            <p class="mb-0 text-muted small">
                produced by Noyb Fundamentals 2026 &copy;
            </p>
            <a href="https://instagram.com/howsey.site?cplk=MWFhbGV2dDJzdWNjdw%3D%3D&utm_source=qr" target="_blank" rel="noopener noreferrer"
               style="color: var(--primary-color); text-decoration: none; line-height: 1;"
               title="Follow HOWSEY on Instagram" aria-label="Follow HOWSEY on Instagram">
                <i class="bi bi-instagram" style="font-size: 1.15rem;"></i>
            </a>
        </div>
    </footer>

    @auth
    <div class="modal fade" id="keyboardShortcutsModal" tabindex="-1" aria-labelledby="keyboardShortcutsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-white">
                    <div>
                        <h5 class="modal-title mb-1" id="keyboardShortcutsModalLabel">Keyboard Shortcuts</h5>
                        <div class="small text-muted">Use these keys for faster navigation. If Windows captures Alt + Shift, use G then the letter shown. Shortcuts pause while typing in a form.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    @foreach($shortcutDefinitions as $shortcut)
                        <div class="shortcut-row px-4 py-3 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                            <div>
                                <div class="fw-semibold">{{ $shortcut['label'] }}</div>
                                <div class="small text-muted">{{ $shortcut['description'] }}</div>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                @foreach($shortcut['combo'] as $keyIndex => $key)
                                    @if($keyIndex > 0)
                                        <span class="shortcut-plus">+</span>
                                    @endif
                                    <span class="shortcut-key">{{ $key }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endauth

    @stack('modals')

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.howseyConfig = {
            enableChatbot: @json($enableChatbot),
            enableScreensaver: @json($enableScreensaver),
            enablePwaUi: @json($enablePwaUi),
            enablePwaInstallUi: @json($enablePwaInstallUi),
            enablePwaNotificationUi: @json($enablePwaNotificationUi),
            userRole: @json(Auth::check() ? (Auth::user()->isAdmin() ? 'admin' : (Auth::user()->isManager() ? 'manager' : 'tenant')) : 'guest'),
            currentRoute: @json(Route::currentRouteName()),
            authenticated: @json(Auth::check()),
            chatbotEndpoint: @json(url('/bot/message')),
            loginRoute: @json(route('login')),
            serviceWorkerUrl: @json(url('/service-worker.js')),
            offlineFallbackUrl: @json(url('/offline.html')),
            pwaIconUrl: @json($branding['pwa_icon_url'] ?? url('/howsey.png')),
            pushRoutes: {
                publicKey: @json(route('push-subscriptions.public-key')),
                subscribe: @json(route('push-subscriptions.subscribe')),
                unsubscribe: @json(route('push-subscriptions.unsubscribe')),
                latest: @json(route('push-subscriptions.latest')),
            },
            shortcutRoutes: @json($shortcutRoutes),
        };
    </script>
    <script src="{{ asset('assets/js/app-shell.js') }}?v={{ $shellAssetVersion }}"></script>
    @stack('scripts')

    @if($enableScreensaver)
        <!-- Screensaver Overlay -->
        <style>
            #screensaver {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 9999;
                background-color: var(--primary-color);
                background-image:
                    linear-gradient(rgba(var(--brand-primary-rgb), 0.74), rgba(18, 10, 29, 0.72)),
                    url('{{ asset("assets/images/hero-bg.jpg") }}');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                display: none;
                align-items: center;
                justify-content: center;
                color: white;
                text-align: center;
            }
            #screensaver h1 {
                text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            }
            @media (max-width: 768px) {
                #screensaver {
                    background-image:
                        linear-gradient(rgba(var(--brand-primary-rgb), 0.58), rgba(18, 10, 29, 0.60)),
                        url('{{ asset("assets/images/hero-bg-mobile.jpg") }}');
                    background-position: center top;
                }
            }
        </style>
        <div id="screensaver">
            <div>
                @if($brandLogoUrl)
                    <div class="mb-3"><img src="{{ $brandLogoUrl }}" alt="{{ $brandDisplayName }} logo" class="brand-logo" style="width: 4rem; height: 4rem;"></div>
                @endif
                <h1 class="display-3 fw-bold brand-title">{{ $brandDisplayName }}</h1>
                <p class="lead fs-3">Property Management Simplified</p>
                <p class="mt-4 opacity-75">
                    <small class="d-none d-lg-inline"><i class="bi bi-mouse me-2"></i>Move mouse or press any key to return</small>
                    <small class="d-lg-none">Tap screen to continue</small>
                </p>
            </div>
        </div>
    @endif

    @if($enableChatbot)
        <!-- Chatbot Widget HTML -->
        <div id="chatbot-widget">
            <button id="chatbot-toggle" type="button">
                <i class="bi bi-chat-dots-fill"></i>
            </button>
        </div>

        <div id="chatbot-window">
            <div id="chatbot-header">
                <span class="fw-bold"><i class="bi bi-robot me-2"></i>{{ $brandDisplayName }} Assistant</span>
                <button class="btn btn-sm text-white" type="button" data-chatbot-close>
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div id="chatbot-messages"></div>
            <div id="chatbot-input-area">
                <input type="text" id="chatbot-input" placeholder="Type your message...">
                <button id="chatbot-send" type="button">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </div>
    @endif

    @if($enablePwaUi)
        <button id="pwa-notification-button" type="button" class="btn btn-outline-primary shadow-sm" style="position: fixed; bottom: 76px; left: 20px; z-index: 1000; display: none; align-items: center; gap: 6px; border-radius: 999px;">
            <i class="bi bi-bell"></i>
            <span>Enable Alerts</span>
        </button>

        <button id="pwa-install-button" type="button" class="btn btn-primary shadow-sm" style="position: fixed; bottom: 20px; left: 20px; z-index: 1000; display: none; align-items: center; gap: 6px; border-radius: 999px;">
            <i class="bi bi-download"></i>
            <span>Install App</span>
        </button>

        <!-- PWA Install Instructions Modal -->
        <div class="modal fade" id="pwaInstallModal" tabindex="-1" aria-labelledby="pwaInstallModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pwaInstallModalLabel">Install {{ $brandDisplayName }} App</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">To install the <span class="brand-title">{{ $brandDisplayName }}</span> app:</p>

                        <div class="d-none d-md-block">
                            <p class="mb-2"><strong>Desktop (Chrome/Edge):</strong></p>
                            <ol class="ps-3 mb-0">
                                <li class="mb-2">Click the install icon <i class="bi bi-laptop"></i> in the address bar.</li>
                                <li>Or go to Menu <i class="bi bi-three-dots-vertical"></i> &gt; <strong class="brand-title">Install {{ $brandDisplayName }}</strong>.</li>
                            </ol>
                        </div>

                        <div class="d-block d-md-none">
                            <div class="mb-3">
                                <p class="mb-2 fw-bold text-dark"><i class="bi bi-android2 text-success me-1"></i> Android (Chrome):</p>
                                <ol class="ps-3 mb-0 small text-dark">
                                    <li class="mb-1">Tap the Menu button <i class="bi bi-three-dots-vertical"></i> (top right).</li>
                                    <li>Tap <strong>Install App</strong> or <strong>Add to Home Screen</strong>.</li>
                                </ol>
                            </div>

                            <div>
                                <p class="mb-2 fw-bold text-dark"><i class="bi bi-apple text-dark me-1"></i> iOS (Safari):</p>
                                <ol class="ps-3 mb-0 small text-dark">
                                    <li class="mb-1">Tap the <strong>Share</strong> button <i class="bi bi-box-arrow-up"></i> (bottom center).</li>
                                    <li>Scroll down and tap <strong>Add to Home Screen</strong> <i class="bi bi-plus-square"></i>.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</body>
</html>
