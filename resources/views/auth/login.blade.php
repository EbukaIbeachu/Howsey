@extends('layouts.app')

@section('content')
@php
    $rootCompanyAdminAccess = $rootCompanyAdminAccess ?? false;
    $isWorkspaceLogin = !$rootCompanyAdminAccess && !empty($loginCompany);
    $loginHeading = $rootCompanyAdminAccess
        ? 'Company Admin Login'
        : (($branding['app_name'] ?? ($loginCompany?->name ?? 'HOWSEY')).' Login');
    $loginSupportText = $rootCompanyAdminAccess
        ? 'Use the company workspace link or slug together with the full credentials of a company admin account. Managers, accountants, and tenants should sign in from their company-specific page instead.'
        : ($branding['app_name'] ?? ($loginCompany?->name ?? 'Property Management System'));
    $brandName = $branding['app_name'] ?? ($loginCompany?->name ?? 'HOWSEY');
    $brandLogo = $branding['logo_url'] ?? null;
    $mobileLoginSupportText = $rootCompanyAdminAccess
        ? 'Secure admin access for company workspaces.'
        : 'Sign in to access your company workspace.';
    $accountIdLabel = $isWorkspaceLogin ? 'Workspace Account ID' : 'Account ID';
    $desktopLoginSupportText = $isWorkspaceLogin
        ? 'Use your workspace account ID and password to continue.'
        : $loginSupportText;
@endphp
<div class="container-fluid px-3 px-lg-4">
    <style>
        .auth-shell {
            min-height: calc(100vh - 5.5rem);
            display: flex;
            align-items: center;
            padding: 2rem 0 3rem;
        }
        .auth-surface {
            position: relative;
            overflow: hidden;
            border-radius: 2rem;
            background:
                radial-gradient(circle at top right, rgba(var(--brand-primary-rgb), 0.18), transparent 34rem),
                linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(248, 245, 255, 0.98));
            border: 1px solid rgba(var(--brand-primary-rgb), 0.14);
            box-shadow: 0 2rem 4rem rgba(20, 16, 38, 0.14);
        }
        .auth-surface::before,
        .auth-surface::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: rgba(var(--brand-primary-rgb), 0.08);
            pointer-events: none;
        }
        .auth-surface::before {
            width: 20rem;
            height: 20rem;
            top: -10rem;
            right: -6rem;
        }
        .auth-surface::after {
            width: 16rem;
            height: 16rem;
            bottom: -8rem;
            left: -4rem;
        }
        .auth-surface > .row {
            position: relative;
            z-index: 1;
        }
        .auth-hero {
            padding: 2.5rem 2.25rem;
            color: #1f2937;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.62), rgba(255, 255, 255, 0.62)),
                url('/assets/images/wallpaper.jpeg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .auth-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 999px;
            padding: 0.5rem 0.9rem;
            background: rgba(var(--brand-primary-rgb), 0.10);
            color: var(--primary-color);
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin-bottom: 1rem;
        }
        .auth-brand-lockup {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            margin-bottom: 1.25rem;
        }
        .auth-brand-logo {
            width: 3.75rem;
            height: 3.75rem;
            border-radius: 1.1rem;
            object-fit: contain;
            background: #ffffff;
            border: 1px solid rgba(var(--brand-primary-rgb), 0.14);
            box-shadow: 0 1rem 2rem rgba(var(--brand-primary-rgb), 0.10);
            padding: 0.55rem;
        }
        .auth-brand-fallback {
            width: 3.75rem;
            height: 3.75rem;
            border-radius: 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(var(--brand-primary-rgb), 0.12), rgba(var(--brand-primary-rgb), 0.22));
            color: var(--primary-color);
            font-size: 1.4rem;
            box-shadow: 0 1rem 2rem rgba(var(--brand-primary-rgb), 0.10);
        }
        .auth-hero h1 {
            font-size: clamp(2rem, 3vw, 3rem);
            font-weight: 800;
            line-height: 1.08;
            margin-bottom: 0.85rem;
            color: #111827;
        }
        .auth-hero-copy {
            font-size: 1rem;
            line-height: 1.75;
            color: #5b6472;
            max-width: 36rem;
            margin-bottom: 1.5rem;
        }
        .auth-hero--workspace-desktop .auth-badge {
            margin-bottom: 1.15rem;
        }
        .auth-hero--workspace-desktop {
            justify-content: flex-start !important;
            padding-top: 1.9rem;
        }
        .auth-hero--workspace-desktop .auth-brand-lockup {
            margin-bottom: 0.8rem;
        }
        .auth-hero--workspace-desktop h1 {
            font-size: clamp(1.95rem, 2.7vw, 2.75rem);
            margin-bottom: 0.7rem;
        }
        .auth-hero--workspace-desktop .auth-hero-copy {
            max-width: 30rem;
            margin-bottom: 1.15rem;
        }
        .auth-trust-grid {
            display: grid;
            gap: 0.85rem;
            margin-bottom: 1.25rem;
        }
        .auth-trust-card {
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            padding: 1rem 1.05rem;
            border-radius: 1.15rem;
            background: rgba(255, 255, 255, 0.76);
            border: 1px solid rgba(var(--brand-primary-rgb), 0.10);
            box-shadow: 0 1rem 1.8rem rgba(15, 23, 42, 0.05);
        }
        .auth-trust-card i {
            width: 2.4rem;
            height: 2.4rem;
            border-radius: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(var(--brand-primary-rgb), 0.12);
            color: var(--primary-color);
            font-size: 1rem;
            flex-shrink: 0;
        }
        .auth-trust-card strong {
            display: block;
            margin-bottom: 0.2rem;
            color: #111827;
        }
        .auth-trust-card span {
            display: block;
            color: #5f6b7a;
            font-size: 0.92rem;
            line-height: 1.55;
        }
        .auth-brand-overline {
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            color: #6b7280;
            margin-bottom: 0.18rem;
        }
        .auth-brand-company-name {
            font-size: clamp(1.55rem, 2.2vw, 2.15rem);
            font-weight: 800;
            line-height: 1.15;
            color: #111827;
        }
        .auth-insight-grid {
            display: grid;
            gap: 0.95rem;
            margin-bottom: 1.5rem;
        }
        .auth-insight-card {
            display: flex;
            gap: 0.9rem;
            align-items: flex-start;
            padding: 1rem 1.1rem;
            border-radius: 1.2rem;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(var(--brand-primary-rgb), 0.10);
            box-shadow: 0 1rem 1.8rem rgba(15, 23, 42, 0.05);
        }
        .auth-insight-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(var(--brand-primary-rgb), 0.12);
            color: var(--primary-color);
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .auth-insight-card h6 {
            margin-bottom: 0.25rem;
            font-weight: 700;
            color: #111827;
        }
        .auth-insight-card p {
            margin: 0;
            font-size: 0.93rem;
            line-height: 1.6;
            color: #5f6b7a;
        }
        .auth-support-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }
        .auth-support-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            padding: 0.55rem 0.9rem;
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid rgba(var(--brand-primary-rgb), 0.12);
            font-size: 0.9rem;
            color: #4b5563;
        }
        .auth-panel {
            padding: 1.2rem;
        }
        .auth-panel-card {
            border-radius: 1.6rem;
            border: 1px solid rgba(var(--brand-primary-rgb), 0.14);
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.10);
            overflow: hidden;
        }
        .auth-panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.5rem 1.5rem 1rem;
        }
        .auth-mobile-brand,
        .auth-mobile-quicklinks {
            display: none;
        }
        .auth-panel-title {
            margin: 0;
            font-size: 1.55rem;
            font-weight: 800;
            color: #111827;
        }
        .auth-panel-subtitle {
            margin: 0.4rem 0 0;
            color: #6b7280;
            line-height: 1.65;
            font-size: 0.96rem;
        }
        .auth-panel-header-copy {
            flex: 1;
        }
        .auth-panel-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.65rem;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--primary-color);
        }
        .help-icon-btn {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            line-height: 1;
            border-color: rgba(var(--brand-primary-rgb), 0.18);
            color: var(--primary-color);
            background: rgba(var(--brand-primary-rgb), 0.06);
        }
        .help-icon-btn:hover,
        .help-icon-btn:focus {
            border-color: rgba(var(--brand-primary-rgb), 0.26);
            color: var(--primary-color);
            background: rgba(var(--brand-primary-rgb), 0.10);
        }
        .auth-panel-body {
            padding: 0 1.5rem 1.5rem;
        }
        .auth-status-card {
            display: flex;
            gap: 0.85rem;
            align-items: center;
            padding: 0.9rem 1rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(var(--brand-primary-rgb), 0.08), rgba(var(--brand-primary-rgb), 0.04));
            border: 1px solid rgba(var(--brand-primary-rgb), 0.12);
            margin-bottom: 1.25rem;
        }
        .auth-status-card i {
            font-size: 1.1rem;
            color: var(--primary-color);
        }
        .auth-status-card strong {
            display: block;
            color: #111827;
        }
        .auth-status-card span {
            display: block;
            font-size: 0.92rem;
            color: #6b7280;
        }
        .auth-primary-actions {
            display: grid;
            gap: 0.85rem;
        }
        .auth-desktop-forgot-link {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 0;
            border: 0;
            background: transparent;
            color: var(--primary-color);
            font-weight: 700;
            text-decoration: none;
            min-height: auto !important;
        }
        .auth-desktop-forgot-link:hover,
        .auth-desktop-forgot-link:focus {
            color: var(--primary-hover);
        }
        .auth-desktop-secondary-panel {
            margin-top: 1.15rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(var(--brand-primary-rgb), 0.10);
        }
        .auth-desktop-secondary-heading {
            margin-bottom: 0.7rem;
            font-size: 0.88rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
        }
        .auth-desktop-secondary-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .auth-desktop-secondary-actions .btn,
        .auth-desktop-secondary-actions a {
            min-height: 2.85rem;
            border-radius: 999px;
            font-weight: 600;
        }
        .auth-desktop-inline-note {
            margin: 0.9rem 0 0;
            font-size: 0.84rem;
            line-height: 1.55;
            color: #6b7280;
        }
        .auth-surface .form-label {
            font-weight: 700;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .auth-surface .form-control,
        .auth-surface .form-select {
            min-height: 3.2rem;
            border-radius: 0.95rem;
            border: 1px solid rgba(var(--brand-primary-rgb), 0.16);
            background: rgba(255, 255, 255, 0.94);
            padding-inline: 1rem;
        }
        .auth-surface .form-control:focus,
        .auth-surface .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.22rem rgba(var(--brand-primary-rgb), 0.16);
        }
        .auth-surface .form-text {
            color: #6b7280;
        }
        .auth-surface .btn {
            min-height: 3rem;
            border-radius: 0.95rem;
            font-weight: 700;
        }
        .auth-surface .btn-primary {
            box-shadow: 0 1rem 2rem rgba(var(--brand-primary-rgb), 0.18);
        }
        .auth-surface .btn-outline-secondary:hover,
        .auth-surface .btn-outline-secondary:focus {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background-color: rgba(var(--brand-primary-rgb), 0.08);
        }
        .auth-surface .btn-outline-warning:hover,
        .auth-surface .btn-outline-warning:focus {
            box-shadow: 0 0.6rem 1.2rem rgba(var(--brand-primary-rgb), 0.12);
        }
        .role-option-card {
            border: 1px solid rgba(var(--brand-primary-rgb), 0.12);
            border-radius: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .role-option-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.75rem 1.5rem rgba(var(--brand-primary-rgb), 0.12);
            border-color: rgba(var(--brand-primary-rgb), 0.35);
            color: inherit;
        }
        .help-icon-btn {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            line-height: 1;
        }
        .data-protection-modal .modal-dialog {
            max-width: 680px;
        }
        .data-protection-modal .modal-body {
            max-height: min(70vh, 32rem);
            overflow-y: auto;
        }
        .auth-footer-note {
            text-align: center;
            margin-top: 1rem;
            color: #6b7280;
        }
        .auth-mobile-brand {
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 1rem;
        }
        .auth-mobile-brand-logo,
        .auth-mobile-brand-fallback {
            width: 3rem;
            height: 3rem;
            border-radius: 1rem;
            flex-shrink: 0;
        }
        .auth-mobile-brand-logo {
            object-fit: contain;
            padding: 0.45rem;
            background: #ffffff;
            border: 1px solid rgba(var(--brand-primary-rgb), 0.14);
            box-shadow: 0 0.8rem 1.5rem rgba(var(--brand-primary-rgb), 0.10);
        }
        .auth-mobile-brand-fallback {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(var(--brand-primary-rgb), 0.12), rgba(var(--brand-primary-rgb), 0.22));
            color: var(--primary-color);
            font-size: 1.05rem;
        }
        .auth-mobile-brand .small {
            color: #6b7280;
        }
        .auth-mobile-brand-label {
            font-size: 0.72rem;
            letter-spacing: 0.08em;
        }
        .auth-mobile-brand-name {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--primary-color);
            line-height: 1.2;
        }
        .auth-mobile-brand .fw-bold {
            color: #111827;
        }
        .auth-mobile-workspace-chip {
            display: none;
            align-items: center;
            justify-content: center;
            width: fit-content;
            max-width: 100%;
            border-radius: 999px;
            padding: 0.5rem 0.95rem;
            margin-bottom: 0.95rem;
            background: rgba(var(--brand-primary-rgb), 0.08);
            border: 1px solid rgba(var(--brand-primary-rgb), 0.12);
            color: var(--primary-color);
            font-size: 0.83rem;
            font-weight: 700;
        }
        .auth-mobile-summary {
            display: none;
            font-size: 0.95rem;
            line-height: 1.55;
            color: #6b7280;
            margin: 0.45rem 0 0;
        }
        .auth-mobile-quicklinks {
            margin-top: 1rem;
            border-top: 1px solid rgba(var(--brand-primary-rgb), 0.10);
            padding-top: 1rem;
        }
        .auth-mobile-quicklinks summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            list-style: none;
            cursor: pointer;
            font-weight: 700;
            color: #374151;
        }
        .auth-mobile-quicklinks summary::-webkit-details-marker {
            display: none;
        }
        .auth-mobile-quicklinks summary::after {
            content: "\F282";
            font-family: bootstrap-icons !important;
            font-size: 0.95rem;
            color: var(--primary-color);
            transition: transform 0.2s ease;
        }
        .auth-mobile-quicklinks[open] summary::after {
            transform: rotate(180deg);
        }
        .auth-mobile-link-grid {
            display: grid;
            gap: 0.75rem;
            margin-top: 0.9rem;
        }
        .auth-mobile-link-grid .btn {
            justify-content: center;
        }
        .auth-mobile-forgot-link,
        .auth-mobile-secondary {
            display: none;
        }
        .auth-mobile-forgot-link {
            width: 100%;
            padding: 0.2rem 0 0;
            min-height: auto !important;
            border: 0;
            background: transparent;
            box-shadow: none;
            color: var(--primary-color);
            font-weight: 700;
            text-decoration: none;
        }
        .auth-mobile-forgot-link:hover,
        .auth-mobile-forgot-link:focus {
            color: var(--primary-hover);
        }
        .auth-mobile-secondary {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 1.1rem;
            border: 1px dashed rgba(var(--brand-primary-rgb), 0.16);
            background: rgba(124, 58, 237, 0.03);
        }
        .auth-mobile-secondary-title {
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #6b7280;
        }
        .auth-mobile-secondary .auth-mobile-link-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.7rem;
            margin-top: 0;
        }
        .auth-mobile-secondary .auth-mobile-link-grid .btn,
        .auth-mobile-secondary .auth-mobile-link-grid a {
            min-height: 2.75rem;
            border-radius: 999px;
            font-weight: 600;
        }
        .auth-mobile-secondary .auth-mobile-link-grid .auth-mobile-link-wide {
            grid-column: 1 / -1;
        }
        .auth-mobile-secondary-note {
            margin: 0.85rem 0 0;
            color: #6b7280;
            font-size: 0.82rem;
            line-height: 1.5;
        }
        @media (max-width: 991.98px) {
            .auth-shell {
                min-height: auto;
                padding-top: 1rem;
            }
            .auth-surface {
                border-radius: 1.6rem;
            }
            .auth-hero {
                padding-bottom: 0;
            }
        }
        @media (max-width: 575.98px) {
            .auth-shell {
                padding-inline: 0.1rem;
            }
            .auth-surface {
                border-radius: 1.3rem;
            }
            .auth-hero,
            .auth-panel {
                padding: 1.2rem;
            }
            .auth-panel-header,
            .auth-panel-body {
                padding-inline: 1.1rem;
            }
            .auth-panel-header {
                padding-top: 1.1rem;
            }
            .auth-brand-lockup {
                align-items: flex-start;
            }
        }
        @media (max-width: 767.98px) {
            .auth-shell {
                align-items: flex-start;
                padding: 0.75rem 0 2rem;
            }
            .auth-surface {
                border-radius: 0;
                border: none;
                background: transparent;
                box-shadow: none;
            }
            .auth-surface::before,
            .auth-surface::after,
            .auth-hero-column,
            .auth-footer-note {
                display: none;
            }
            .auth-panel {
                padding: 0;
            }
            .auth-panel-card {
                border-radius: 1.5rem;
                box-shadow: 0 1.5rem 2.5rem rgba(15, 23, 42, 0.10);
                background: rgba(255, 255, 255, 0.98);
            }
            .auth-panel-header {
                display: block;
                padding: 1.15rem 1.15rem 0.8rem;
            }
            .auth-mobile-brand,
            .auth-mobile-summary,
            .auth-mobile-quicklinks {
                display: block;
            }
            .auth-mobile-brand {
                display: flex;
                margin-bottom: 0.65rem;
            }
            .auth-mobile-workspace-chip,
            .auth-mobile-forgot-link,
            .auth-mobile-secondary {
                display: block;
            }
            .auth-panel-title {
                font-size: 2rem;
                margin-bottom: 0.2rem;
                line-height: 1.05;
            }
            .auth-panel-subtitle {
                display: none;
            }
            .help-icon-btn {
                position: absolute;
                top: 1.05rem;
                right: 1.05rem;
                width: 2.25rem;
                height: 2.25rem;
            }
            .auth-panel-card {
                position: relative;
            }
            .auth-panel-body {
                padding: 0 1.15rem 1.15rem;
            }
            .auth-panel-card--workspace-login .auth-panel-header {
                padding-bottom: 0.45rem;
            }
            .auth-panel-card--workspace-login .help-icon-btn,
            .auth-panel-card--workspace-login .auth-status-card,
            .auth-panel-card--workspace-login .auth-login-reminder,
            .auth-panel-card--workspace-login .auth-desktop-forgot-link,
            .auth-panel-card--workspace-login .auth-desktop-secondary-panel,
            .auth-panel-card--workspace-login .auth-mobile-quicklinks {
                display: none;
            }
            .auth-panel-card--workspace-login .auth-panel-body {
                padding-top: 0.2rem;
            }
            .auth-panel-card--workspace-login .mb-3 {
                margin-bottom: 0.9rem !important;
            }
            .auth-panel-card--workspace-login .form-label {
                margin-bottom: 0.45rem;
            }
            .auth-status-card {
                padding: 0.8rem 0.9rem;
                margin-bottom: 1rem;
            }
            .auth-status-card span {
                font-size: 0.88rem;
            }
            #pwa-install-button,
            #pwa-notification-button {
                display: none !important;
            }
            .auth-surface .form-control,
            .auth-surface .form-select,
            .auth-surface .btn {
                min-height: 3.15rem;
            }
            .auth-desktop-forgot-link,
            .auth-desktop-secondary {
                display: none;
            }
        }
    </style>
    <div class="auth-shell">
        <div class="container">
            <div class="auth-surface">
                <div class="row g-0 align-items-stretch">
                    <div class="{{ $isWorkspaceLogin ? 'col-lg-6' : 'col-lg-7' }} auth-hero-column">
                        <div class="auth-hero h-100 d-flex flex-column justify-content-center {{ $isWorkspaceLogin ? 'auth-hero--workspace-desktop' : '' }}">
                            @if($isWorkspaceLogin)
                                <div class="auth-badge">
                                    <i class="bi bi-stars"></i>
                                    <span>Branded company workspace</span>
                                </div>
                                <div class="auth-brand-lockup">
                                    @if(!empty($brandLogo))
                                        <img src="{{ $brandLogo }}" alt="{{ $brandName }} logo" class="auth-brand-logo">
                                    @else
                                        <span class="auth-brand-fallback">
                                            <i class="bi bi-buildings"></i>
                                        </span>
                                    @endif
                                    <div>
                                        <div class="text-uppercase fw-bold auth-brand-overline">Welcome to</div>
                                        <div class="auth-brand-company-name">{{ $brandName }}</div>
                                    </div>
                                </div>
                                <p class="auth-hero-copy">
                                    Sign in with the account issued for this workspace. Your company branding, access rules, and recovery path stay tied to this workspace only.
                                </p>

                                <div class="auth-trust-grid">
                                    <div class="auth-trust-card">
                                        <i class="bi bi-key"></i>
                                        <div>
                                            <strong>Use your workspace account ID</strong>
                                            <span>Every sign-in stays matched to the correct company workspace and role permissions.</span>
                                        </div>
                                    </div>
                                    <div class="auth-trust-card">
                                        <i class="bi bi-shield-lock"></i>
                                        <div>
                                            <strong>Recovery stays protected</strong>
                                            <span>Forgotten passwords are reviewed through the workspace recovery flow instead of opening the main sign-in screen.</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="auth-support-strip">
                                    <span class="auth-support-chip"><i class="bi bi-phone"></i> Focused desktop sign-in</span>
                                    <span class="auth-support-chip"><i class="bi bi-life-preserver"></i> Guided recovery support</span>
                                </div>
                            @else
                                <div class="auth-badge">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Secure Workspace Access</span>
                                </div>
                                <div class="auth-brand-lockup">
                                    @if(!empty($brandLogo))
                                        <img src="{{ $brandLogo }}" alt="{{ $brandName }} logo" class="auth-brand-logo">
                                    @else
                                        <span class="auth-brand-fallback">
                                            <i class="bi bi-buildings"></i>
                                        </span>
                                    @endif
                                    <div>
                                        <div class="text-uppercase small fw-bold text-muted mb-1">Welcome to {{ $brandName }}</div>
                                        <h1>{{ $loginHeading }}</h1>
                                    </div>
                                </div>
                                <p class="auth-hero-copy">
                                    {{ $rootCompanyAdminAccess ? 'Enter through the main HOWSEY access point with your company workspace and full admin credentials. This route is reserved for workspace administrators and keeps each company environment clearly separated.' : 'Sign in to access your company workspace, manage operations, and stay connected to properties, tenants, payments, and service activity from one clean control point.' }}
                                </p>

                                <div class="auth-insight-grid">
                                    <div class="auth-insight-card">
                                        <span class="auth-insight-icon"><i class="bi bi-person-badge"></i></span>
                                        <div>
                                            <h6>Role-aware access</h6>
                                            <p>Admins, managers, accountants, and tenants each land in the right workspace with the permissions meant for them.</p>
                                        </div>
                                    </div>
                                    <div class="auth-insight-card">
                                        <span class="auth-insight-icon"><i class="bi bi-phone"></i></span>
                                        <div>
                                            <h6>Built for desktop and mobile</h6>
                                            <p>The sign-in flow stays readable and balanced across laptops, tablets, and phones without feeling cramped.</p>
                                        </div>
                                    </div>
                                    <div class="auth-insight-card">
                                        <span class="auth-insight-icon"><i class="bi bi-lock"></i></span>
                                        <div>
                                            <h6>Protected records</h6>
                                            <p>Access happens through authenticated workspace flows designed for property, agreement, and tenant data protection.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="auth-support-strip">
                                    <span class="auth-support-chip"><i class="bi bi-stars"></i> Branded company workspace</span>
                                    <span class="auth-support-chip"><i class="bi bi-key"></i> Account ID sign-in</span>
                                    <span class="auth-support-chip"><i class="bi bi-life-preserver"></i> Guided recovery support</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="{{ $isWorkspaceLogin ? 'col-lg-6' : 'col-lg-5' }}">
                        <div class="auth-panel h-100 d-flex align-items-center">
                            <div class="auth-panel-card w-100 {{ $isWorkspaceLogin ? 'auth-panel-card--workspace-login' : '' }}">
                                <div class="auth-panel-header">
                                    <div class="auth-mobile-brand">
                                        @if(!empty($brandLogo))
                                            <img src="{{ $brandLogo }}" alt="{{ $brandName }} logo" class="auth-mobile-brand-logo">
                                        @else
                                            <span class="auth-mobile-brand-fallback">
                                                <i class="bi bi-buildings"></i>
                                            </span>
                                        @endif
                                        <div>
                                            <div class="small text-uppercase fw-semibold auth-mobile-brand-label">Welcome to</div>
                                            <div class="auth-mobile-brand-name">{{ $brandName }}</div>
                                        </div>
                                    </div>
                                    @if($isWorkspaceLogin)
                                        <div class="auth-mobile-workspace-chip">Secure company workspace</div>
                                    @endif
                                    <div class="auth-panel-header-copy">
                                        @if($isWorkspaceLogin)
                                            <div class="auth-panel-kicker">Workspace sign-in</div>
                                        @endif
                                        <h2 class="auth-panel-title">Sign in</h2>
                                        <p class="auth-panel-subtitle">{{ $desktopLoginSupportText }}</p>
                                        @if($isWorkspaceLogin)
                                            <p class="auth-mobile-summary">{{ $mobileLoginSupportText }}</p>
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm help-icon-btn" data-bs-toggle="modal" data-bs-target="#dataProtectionModal" aria-label="Data protection notice" title="Data protection notice">?</button>
                                </div>
                                <div class="auth-panel-body">
                                    @if (session('success'))
                                        <div class="alert alert-success">
                                            {{ session('success') }}
                                        </div>
                                    @endif

                                    @if (session('expiredCompanySlug'))
                                        <div class="alert alert-warning">
                                            <div class="fw-semibold mb-1">Workspace renewal required</div>
                                            <div class="small mb-3">This company workspace has expired. Renew the annual plan to restore access for every user under the workspace.</div>
                                            <a href="{{ route('companies.subscription.show', session('expiredCompanySlug')) }}" class="btn btn-sm btn-outline-primary">Open Renewal Page</a>
                                        </div>
                                    @endif

                                    <div class="auth-status-card">
                                        <i class="bi bi-person-check"></i>
                                        <div>
                                            <strong>{{ $rootCompanyAdminAccess ? 'Company admin entry point' : 'Workspace login ready' }}</strong>
                                            <span>{{ $rootCompanyAdminAccess ? 'Use the correct company workspace link or slug together with the admin account credentials.' : 'Use your account ID and password to continue into the correct branded workspace.' }}</span>
                                        </div>
                                    </div>
                                    <div class="alert alert-light border small auth-login-reminder">
                                        <strong>Important:</strong> Keep your Account ID and login phone number safe.
                                    </div>
                                    @unless($isWorkspaceLogin)
                                        <p class="auth-mobile-summary">{{ $mobileLoginSupportText }}</p>
                                    @endunless

                                    <form method="POST" action="{{ $loginCompany ? route('companies.login.attempt', $loginCompany->slug) : route('login', $rootCompanyAdminAccess ? ['entry' => 'company-admin'] : []) }}">
                                        @csrf
                                        @if($rootCompanyAdminAccess)
                                            <input type="hidden" name="root_company_admin_access" value="1">
                                        @endif

                                        @if($rootCompanyAdminAccess)
                                            <div class="mb-3">
                                                <label for="workspace" class="form-label">Company Workspace URL or Slug</label>
                                                <input type="text" class="form-control @error('workspace') is-invalid @enderror" id="workspace" name="workspace" value="{{ old('workspace') }}" placeholder="https://howsey.site/c/acme-housing or acme-housing" required>
                                                @error('workspace')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Only company admin accounts can use this HOWSEY homepage access point.</div>
                                            </div>
                                        @endif

                                        <div class="mb-3">
                                            <label for="email" class="form-label">{{ $accountIdLabel }}</label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="name@company-slug.howsey.site" required autofocus>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                        </div>

                                        <div class="auth-primary-actions">
                                            <button type="submit" class="btn btn-primary">Login</button>
                                            @if(Route::has('password-recovery-requests.store'))
                                                <button type="button" class="btn btn-link auth-desktop-forgot-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordRequestModal">Forgot Password?</button>
                                                <button type="button" class="btn btn-link auth-mobile-forgot-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordRequestModal">Forgot Password?</button>
                                            @endif
                                            @unless($isWorkspaceLogin)
                                                <a href="{{ $loginCompany ? route('companies.portal', $loginCompany->slug) : url('/') }}" class="btn btn-outline-secondary auth-desktop-secondary">Home</a>
                                                @unless($rootCompanyAdminAccess)
                                                    <button type="button" class="btn btn-outline-secondary auth-desktop-secondary" data-bs-toggle="modal" data-bs-target="#registerRoleModal">Register New Account</button>
                                                @endunless
                                            @endunless
                                        </div>
                                    </form>

                                    @if($isWorkspaceLogin)
                                        <div class="auth-desktop-secondary-panel">
                                            <div class="auth-desktop-secondary-heading">More options</div>
                                            <div class="auth-desktop-secondary-actions">
                                                <a href="{{ route('companies.portal', $loginCompany->slug) }}" class="btn btn-outline-secondary">Home</a>
                                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#registerRoleModal">Register New Account</button>
                                            </div>
                                            <p class="auth-desktop-inline-note">Keep your Account ID and login phone number safe. Sign-in uses your Account ID, while recovery matches your login phone number.</p>
                                        </div>
                                        <div class="auth-mobile-secondary">
                                            <div class="auth-mobile-secondary-title">More options</div>
                                            <div class="auth-mobile-link-grid">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#registerRoleModal">Register</button>
                                                <a href="{{ route('companies.portal', $loginCompany->slug) }}" class="btn btn-outline-secondary">Home</a>
                                                <button type="button" class="btn btn-outline-secondary auth-mobile-link-wide" data-bs-toggle="modal" data-bs-target="#dataProtectionModal">Data Protection Notice</button>
                                            </div>
                                            <p class="auth-mobile-secondary-note">Keep your Account ID and login phone number safe. Sign-in uses your Account ID, while recovery matches your login phone number.</p>
                                        </div>
                                    @else
                                        <details class="auth-mobile-quicklinks">
                                            <summary>More options</summary>
                                            <div class="auth-mobile-link-grid">
                                                @if(Route::has('password-recovery-requests.store'))
                                                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#forgotPasswordRequestModal">Forgot Password</button>
                                                @endif
                                                @unless($rootCompanyAdminAccess)
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#registerRoleModal">Register New Account</button>
                                                @endunless
                                                <a href="{{ $loginCompany ? route('companies.portal', $loginCompany->slug) : url('/') }}" class="btn btn-outline-secondary">Home</a>
                                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#dataProtectionModal">Data Protection Notice</button>
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="auth-footer-note">
                <small>{{ $rootCompanyAdminAccess ? 'Need recovery support? Use the request flow in the sign-in panel so the right team can review it.' : 'Use your company workspace link for the most direct branded sign-in experience.' }}</small>
            </div>
        </div>
    </div>
</div>

<div class="modal fade data-protection-modal" id="dataProtectionModal" tabindex="-1" aria-labelledby="dataProtectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dataProtectionModalLabel">Data Protection Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">This platform handles personal information in line with applicable Nigerian data protection principles.</p>
                <ul class="mb-0 ps-3">
                    <li>Your login details and account records are collected only for authentication, tenancy administration, and property management operations within this system.</li>
                    <li>Your information is not intended for unrelated use, and access should be limited to authorized personnel who manage the platform.</li>
                    <li>You are advised to keep your account ID, phone number, and password secure and not share them with unauthorized persons.</li>
                    <li>By continuing to use the system, you acknowledge that your data may be processed for legitimate operational, record-keeping, compliance, and security purposes.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@unless($rootCompanyAdminAccess)
    <div class="modal fade" id="registerRoleModal" tabindex="-1" aria-labelledby="registerRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerRoleModalLabel">Choose your role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Select the registration path that matches how you will use the HOWSEY system.</p>
                    <div class="d-grid gap-3">
                        @if(Route::has('register'))
                            <a href="{{ $loginCompany ? route('companies.register', $loginCompany->slug) : route('register') }}" class="role-option-card p-3">
                                <div class="fw-bold text-primary mb-1">Manager or Accountant</div>
                                <div class="small text-muted">Create a staff account to manage properties, finance, and operations.</div>
                            </a>
                        @endif
                        @if(Route::has('register.tenant'))
                            <a href="{{ $loginCompany ? route('companies.register.tenant', $loginCompany->slug) : route('register.tenant') }}" class="role-option-card p-3">
                                <div class="fw-bold text-primary mb-1">Tenant</div>
                                <div class="small text-muted">Create a tenant account to access your tenancy profile and property services.</div>
                            </a>
                        @endif
                        <a href="{{ $loginCompany ? route('companies.portal', $loginCompany->slug) : url('/') }}" class="role-option-card p-3">
                            <div class="fw-bold text-primary mb-1">Back to Home</div>
                            <div class="small text-muted">Return to the home page and continue from the Get Started flow.</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endunless

@if(Route::has('password-recovery-requests.store'))
    <div class="modal fade" id="forgotPasswordRequestModal" tabindex="-1" aria-labelledby="forgotPasswordRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordRequestModalLabel">Request Password Recovery</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('password-recovery-requests.store') }}">
                    @csrf
                    @if($rootCompanyAdminAccess)
                        <input type="hidden" name="root_company_admin_access" value="1">
                    @endif
                    <div class="modal-body">
                        <p class="text-muted">{{ $rootCompanyAdminAccess ? 'Submit the company workspace link or slug together with the workspace admin login phone number. A HOWSEY super admin will review the matched account before your password is reset.' : 'Submit the login phone number saved on your account. The reviewer will match it to your workspace account before approving a reset.' }}</p>
                        <div class="alert alert-warning small">
                            Keep your Account ID and login phone number safe.
                        </div>
                        @if($rootCompanyAdminAccess)
                            <div class="mb-3">
                                <label for="recovery_workspace" class="form-label">Company Workspace URL or Slug</label>
                                <input type="text" class="form-control @error('recovery_workspace', 'forgotPassword') is-invalid @enderror" id="recovery_workspace" name="recovery_workspace" value="{{ old('recovery_workspace', old('workspace')) }}" placeholder="https://howsey.site/c/acme-housing or acme-housing" required>
                                @error('recovery_workspace', 'forgotPassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                        <div class="mb-0">
                            <label for="recovery_phone" class="form-label">Login Phone Number</label>
                            <input type="text" class="form-control @error('recovery_phone', 'forgotPassword') is-invalid @enderror" id="recovery_phone" name="recovery_phone" value="{{ old('recovery_phone') }}" required>
                            @error('recovery_phone', 'forgotPassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if(Route::has('password-recovery-requests.store') && $errors->forgotPassword->any())
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('forgotPasswordRequestModal');
            if (modalElement) {
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            }
        });
    </script>
    @endpush
@endif
@endsection
