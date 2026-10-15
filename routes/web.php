<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\CompanyApplicationController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\CompanySubscriptionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\ManagerEditRequestController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PasswordRecoveryRequestController;
use App\Http\Controllers\PublicCompanyRegistrationController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Models\Company;
use App\Models\User;
use App\Support\CurrentCompany;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/howsey.png', function () {
    $logoPath = base_path('howsey.png');

    if (! file_exists($logoPath)) {
        abort(404);
    }

    return response()->file($logoPath, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

Route::get('/pwa-icon.png', function (Request $request) {
    $iconPath = base_path('howsey.png');

    if (! file_exists($iconPath)) {
        abort(404);
    }

    return response()->file($iconPath, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

Route::get('/manifest.webmanifest', function () {
    $company = app(CurrentCompany::class)->get();
    $manifest = [
        'name' => $company?->name ?: 'HOWSEY',
        'short_name' => $company?->name ?: 'HOWSEY',
        'description' => ($company?->name ?: 'HOWSEY').' property management system.',
        'start_url' => $company ? route('companies.portal', $company->slug) : '/',
        'scope' => '/',
        'display' => 'standalone',
        'theme_color' => $company?->settings?->primary_color ?: '#7c3aed',
        'background_color' => '#fdfbf7',
        'icons' => [
            [
                'src' => '/howsey.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
        ],
    ];

    return response()->json($manifest)->header('Content-Type', 'application/manifest+json');
});

Route::get('/', function () {
    $portalCompany = auth()->check() && auth()->user()->isSuperAdmin()
        ? null
        : app(CurrentCompany::class)->get();

    if ($portalCompany && ! request()->boolean('platform')) {
        return redirect()->route('companies.portal', $portalCompany->slug);
    }

    return view('welcome', ['portalCompany' => $portalCompany]);
});

Route::get('/company/register', [PublicCompanyRegistrationController::class, 'create'])->name('companies.public.create');
Route::post('/company/register', [PublicCompanyRegistrationController::class, 'store'])->name('companies.public.store');
Route::get('/company/register/paystack/callback', [PublicCompanyRegistrationController::class, 'paystackCallback'])->name('companies.public.paystack.callback');
Route::get('/company/register/status/{companyApplication}', [PublicCompanyRegistrationController::class, 'applicationStatus'])->name('companies.public.application-status');
Route::get('/company/subscription/paystack/callback', [CompanySubscriptionController::class, 'paystackCallback'])->name('companies.subscription.paystack.callback');
Route::get('/c/{company}/subscription', [CompanySubscriptionController::class, 'show'])->name('companies.subscription.show');
Route::post('/c/{company}/subscription/renew', [CompanySubscriptionController::class, 'initiateRenewal'])->name('companies.subscription.renew');

Route::get('/storage/{path}', function ($path) {
    $path = storage_path('app/public/'.$path);

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->where('path', '.*');

// Chatbot Route - Fallback for GET requests (debugging)
Route::get('/bot/message', function () {
    return response()->json(['response' => 'Chatbot endpoint is active (POST required).', 'status' => 'success'], 200);
});

// Chatbot Route - Direct Closure for reliability
Route::post('/bot/message', function (Request $request) {
    try {
        $request->validate(['message' => 'required|string']);
        $message = $request->input('message');

        // Simple fallback logic directly here to avoid Controller issues
        $lowerMsg = strtolower($message);

        // Determine User Role for Context
        $user = Auth::user();
        $role = 'guest';
        if ($user) {
            if ($user->isAdmin()) {
                $role = 'admin';
            } elseif ($user->isAccountant()) {
                $role = 'accountant';
            } elseif ($user->isManager()) {
                $role = 'manager';
            } elseif ($user->isTenant()) {
                $role = 'tenant';
            }
        }

        $roleRouteAccess = [
            'admin' => [
                'dashboard',
                'dashboard.analytics',
                'buildings.index',
                'tenants.index',
                'rents.index',
                'payments.index',
                'maintenance.index',
                'users.index',
                'roles.index',
                'accounting.index',
            ],
            'manager' => [
                'dashboard',
                'buildings.index',
                'tenants.index',
                'rents.index',
                'payments.index',
                'maintenance.index',
                'users.index',
            ],
            'tenant' => [
                'dashboard',
                'maintenance.index',
            ],
            'accountant' => [
                'accounting.index',
            ],
            'guest' => [],
        ];

        $canAccessRoute = function (string $routeName) use ($roleRouteAccess, $role): bool {
            return in_array($routeName, $roleRouteAccess[$role] ?? [], true);
        };

        $buildRouteLink = function (string $routeName, string $label) use ($canAccessRoute) {
            if (! $canAccessRoute($routeName) || ! Route::has($routeName)) {
                return null;
            }

            return '<a href="'.e(route($routeName)).'">'.e($label).'</a>';
        };

        $homeUrl = $user && $user->isAccountant() && Route::has('accounting.index')
            ? route('accounting.index')
            : url('/');

        $navLinks = [];
        $navLinks[] = '<a href="'.e($homeUrl).'">Home</a>';

        if ($role !== 'accountant') {
            $dash = $buildRouteLink('dashboard', 'Dashboard');
            if ($dash) {
                $navLinks[] = $dash;
            }
        }

        if ($role === 'admin' || $role === 'manager') {
            foreach ([
                ['buildings.index', 'Buildings'],
                ['tenants.index', 'Tenants'],
                ['rents.index', 'Rentals'],
                ['payments.index', 'Payments'],
                ['maintenance.index', 'Maintenance'],
                ['users.index', 'Users'],
            ] as $item) {
                $link = $buildRouteLink($item[0], $item[1]);
                if ($link) {
                    $navLinks[] = $link;
                }
            }

            if ($role === 'admin') {
                $roles = $buildRouteLink('roles.index', 'Roles');
                if ($roles) {
                    $navLinks[] = $roles;
                }

                $analytics = $buildRouteLink('dashboard.analytics', 'Analytics');
                if ($analytics) {
                    $navLinks[] = $analytics;
                }
            }
        }

        if ($role === 'tenant') {
            $maintenance = $buildRouteLink('maintenance.index', 'Request Maintenance');
            if ($maintenance) {
                $navLinks[] = $maintenance;
            }
        }

        if ($role === 'admin' || $role === 'accountant') {
            $accounting = $buildRouteLink('accounting.index', 'Accounting');
            if ($accounting) {
                $navLinks[] = $accounting;
            }
        }

        $navText = implode(' • ', array_values(array_unique($navLinks)));

        $response = "Available sections: {$navText}.";

        // Greetings
        if (str_contains($lowerMsg, 'hello') || str_contains($lowerMsg, 'hi') || str_contains($lowerMsg, 'hey') || str_contains($lowerMsg, 'morning') || str_contains($lowerMsg, 'evening')) {
            if ($role === 'admin' || $role === 'manager') {
                $dashboardLink = $buildRouteLink('dashboard', 'dashboard') ?: 'dashboard';
                $response = 'Welcome back, '.($user ? $user->name : 'there')."! You can start from your {$dashboardLink}.";
            } elseif ($role === 'accountant') {
                $accountingLink = $buildRouteLink('accounting.index', 'Accounting') ?: 'Accounting';
                $response = 'Welcome back, '.($user ? $user->name : 'there')."! You can start from {$accountingLink}.";
            } elseif ($role === 'tenant') {
                $maintenanceLink = $buildRouteLink('maintenance.index', 'Request Maintenance') ?: 'Request Maintenance';
                $dashboardLink = $buildRouteLink('dashboard', 'Dashboard') ?: 'Dashboard';
                $response = 'Hi '.($user ? $user->name : 'there').'! You can use '.$dashboardLink.' and '.$maintenanceLink.'.';
            } else {
                $response = 'Hi there! Welcome to HOWSEY. I can help you with Maintenance, Payments, Lease details, or General Support. How can I assist you today?';
            }
        }
        elseif (str_contains($lowerMsg, 'menu') || str_contains($lowerMsg, 'nav') || str_contains($lowerMsg, 'navbar') || str_contains($lowerMsg, 'options') || str_contains($lowerMsg, 'where can')) {
            $response = "Available sections: {$navText}.";
        }
        elseif (str_contains($lowerMsg, 'accounting') || str_contains($lowerMsg, 'finance') || str_contains($lowerMsg, 'ledger') || str_contains($lowerMsg, 'audit')) {
            $accountingLink = $buildRouteLink('accounting.index', 'Accounting');
            if ($accountingLink) {
                $response = "Open {$accountingLink}.";
            } else {
                $response = 'Accounting is not available for your role.';
            }
        }
        elseif (str_contains($lowerMsg, 'analytics') || str_contains($lowerMsg, 'analysis') || str_contains($lowerMsg, 'graph') || str_contains($lowerMsg, 'chart')) {
            $analyticsLink = $buildRouteLink('dashboard.analytics', 'Analytics');

            if ($role === 'admin' && $analyticsLink) {
                $response = "Open {$analyticsLink}.";
            } elseif ($role === 'manager' || $role === 'accountant') {
                $response = 'Dashboard analytics is available to admin only. You can still use the dashboard revenue overview shown on your dashboard page.';
            } else {
                $response = 'Dashboard analytics is available to admin only.';
            }
        }
        // Financials (Rent, Pay, Bill)
        elseif (str_contains($lowerMsg, 'rent') || str_contains($lowerMsg, 'pay') || str_contains($lowerMsg, 'bill') || str_contains($lowerMsg, 'balance') || str_contains($lowerMsg, 'invoice')) {
            if ($role === 'admin' || $role === 'manager') {
                $paymentsLink = $buildRouteLink('payments.index', 'Payments') ?: 'Payments';
                $response = "Open {$paymentsLink} to track collections and overdue accounts.";
            } elseif ($role === 'accountant') {
                $accountingLink = $buildRouteLink('accounting.index', 'Accounting') ?: 'Accounting';
                $response = "Open {$accountingLink} to review and record finance entries.";
            } elseif ($role === 'tenant') {
                $response = 'You can check your outstanding balance and make secure payments from the Payments section in your main menu.';
            } else {
                $response = 'Tenants can login to pay rent. If you are inquiring about rental rates, please check our listings page.';
            }
        }
        // Maintenance (Repair, Fix, Broken)
        elseif (str_contains($lowerMsg, 'maintenance') || str_contains($lowerMsg, 'repair') || str_contains($lowerMsg, 'fix') || str_contains($lowerMsg, 'broken') || str_contains($lowerMsg, 'leak') || str_contains($lowerMsg, 'damage')) {
            if ($role === 'admin' || $role === 'manager') {
                $maintenanceLink = $buildRouteLink('maintenance.index', 'Maintenance') ?: 'Maintenance';
                $response = "Open {$maintenanceLink} to manage maintenance requests.";
            } elseif ($role === 'tenant') {
                $maintenanceLink = $buildRouteLink('maintenance.index', 'Request Maintenance') ?: 'Request Maintenance';
                $response = "To report an issue, open {$maintenanceLink} and submit a request.";
            } elseif ($role === 'accountant') {
                $response = 'Maintenance requests are handled by Operations. Please contact Admin or Manager.';
            } else {
                $response = 'For urgent building maintenance issues, please contact our 24/7 facility manager at (555) 999-8888.';
            }
        }
        // Tenants / Lease (Agreement, Contract)
        elseif (str_contains($lowerMsg, 'tenant') || str_contains($lowerMsg, 'lease') || str_contains($lowerMsg, 'contract') || str_contains($lowerMsg, 'agreement') || str_contains($lowerMsg, 'renew')) {
            if ($role === 'admin' || $role === 'manager') {
                $tenantsLink = $buildRouteLink('tenants.index', 'Tenants') ?: 'Tenants';
                $rentsLink = $buildRouteLink('rents.index', 'Rentals') ?: 'Rentals';
                $response = "Open {$tenantsLink} and {$rentsLink} to manage tenants and leases.";
            } elseif ($role === 'accountant') {
                $response = 'Lease management is handled by Admin or Manager.';
            } elseif ($role === 'tenant') {
                $response = 'Your active lease details, including renewal dates and signed copies, are available in the Rentals section that you can open from your main menu.';
            } else {
                $response = "Are you looking to become a tenant? Please visit our 'Available Units' page to apply.";
            }
        }
        // Account / Profile
        elseif (str_contains($lowerMsg, 'password') || str_contains($lowerMsg, 'login') || str_contains($lowerMsg, 'profile') || str_contains($lowerMsg, 'email') || str_contains($lowerMsg, 'account')) {
            $response = "To update your personal information or change your password, click on your user avatar in the top right corner and select 'Profile'.";
        }
        // General Info / Location
        elseif (str_contains($lowerMsg, 'location') || str_contains($lowerMsg, 'address') || str_contains($lowerMsg, 'where') || str_contains($lowerMsg, 'office')) {
            $response = 'Our main office is located at 123 Property Lane, Real Estate City. We are open Mon-Fri, 9am-5pm.';
        }
        // Contact / Support
        elseif (str_contains($lowerMsg, 'contact') || str_contains($lowerMsg, 'phone') || str_contains($lowerMsg, 'support') || str_contains($lowerMsg, 'help')) {
            if ($role === 'admin') {
                $response = 'System Support: For technical server issues, please contact the IT department. For operational support, check the internal wiki.';
            } else {
                $response = 'You can reach our support team at support@howsey.com or call us at (555) 123-4567 during business hours.';
            }
        }

        // Try Ollama if configured (optional)
        try {
            $ollamaUrl = env('OLLAMA_API_URL', 'http://localhost:11434/api/generate');
            $http = new Client(['timeout' => 2]); // Short timeout
            $res = $http->post($ollamaUrl, [
                'json' => [
                    'model' => env('OLLAMA_MODEL', 'llama3'),
                    'prompt' => $message,
                    'stream' => false,
                ],
            ]);
            $data = json_decode($res->getBody(), true);
            if (isset($data['response'])) {
                $response = $data['response'];
            }
        } catch (Exception $e) {
            // Ollama failed, keep fallback response
        }

        return response()->json(['response' => $response, 'status' => 'success']);

    } catch (Throwable $e) {
        return response()->json(['response' => 'Error: '.$e->getMessage()], 200);
    }
})->name('chatbot.send');

// Test Route to confirm file is updated
Route::get('/chatbot/status', function () {
    return 'Chatbot System is Active';
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('company.subscription.active')->group(function () {
    Route::get('/c/{company}/login', [AuthController::class, 'showLogin'])->name('companies.login');
    Route::post('/c/{company}/login', [AuthController::class, 'login'])->name('companies.login.attempt');
    Route::get('/c/{company}', [PublicCompanyRegistrationController::class, 'showPortal'])->name('companies.portal');
    Route::get('/c/{company}/register', [RegisterController::class, 'showCompanyRegistrationForm'])->name('companies.register');
    Route::post('/c/{company}/register', [RegisterController::class, 'registerForCompany'])->name('companies.register.store');
    Route::get('/c/{company}/register-tenant', [RegisterController::class, 'showCompanyTenantRegistrationForm'])->name('companies.register.tenant');
    Route::post('/c/{company}/register-tenant', [RegisterController::class, 'registerTenantForCompany'])->name('companies.register.tenant.store');
});
Route::post('/forgot-password/request', [PasswordRecoveryRequestController::class, 'store'])->name('password-recovery-requests.store');
Route::get('/logout', function () {
    if (auth()->check()) {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    return redirect()->route('login');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Registration Routes
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Tenant Registration Routes
Route::get('/register-tenant', [RegisterController::class, 'showTenantRegistrationForm'])->name('register.tenant');
Route::post('/register-tenant', [RegisterController::class, 'registerTenant']);

// Admin Registration (Separate/Hidden)
Route::get('/register-admin', [RegisterController::class, 'showAdminRegistrationForm'])->name('register.admin.form');
Route::post('/register-admin', [RegisterController::class, 'registerAdmin'])->name('register.admin');

Route::middleware(['auth', 'prevent-back-history', 'company.subscription.active'])->group(function () {
    Route::post('/super-admin/view-company', function (Request $request) {
        abort_unless(auth()->check() && auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $request->session()->put('howsey_super_admin_view_company_id', (int) $validated['company_id']);

        return back();
    })->name('super-admin.view-company');

    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('/account/profile-image', [AccountController::class, 'updateTenantProfileImage'])->name('account.update-profile-image');
    Route::patch('/account/phone', [AccountController::class, 'updatePhone'])->name('account.update-phone');
    Route::patch('/account/password', [AccountController::class, 'updatePassword'])->name('account.update-password');
    Route::get('/push-subscriptions/public-key', [PushSubscriptionController::class, 'publicKey'])->name('push-subscriptions.public-key');
    Route::post('/push-subscriptions/subscribe', [PushSubscriptionController::class, 'subscribe'])->name('push-subscriptions.subscribe');
    Route::post('/push-subscriptions/unsubscribe', [PushSubscriptionController::class, 'unsubscribe'])->name('push-subscriptions.unsubscribe');
    Route::get('/push-subscriptions/latest', [PushSubscriptionController::class, 'latest'])->name('push-subscriptions.latest');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/password-recovery-requests', [PasswordRecoveryRequestController::class, 'index'])->name('password-recovery-requests.index');
    Route::patch('/password-recovery-requests/{passwordRecoveryRequest}/approve', [PasswordRecoveryRequestController::class, 'approve'])->name('password-recovery-requests.approve');
    Route::patch('/password-recovery-requests/{passwordRecoveryRequest}/reject', [PasswordRecoveryRequestController::class, 'reject'])->name('password-recovery-requests.reject');
    Route::get('/manager-edit-requests', [ManagerEditRequestController::class, 'index'])->name('manager-edit-requests.index');
    Route::post('/manager-edit-requests', [ManagerEditRequestController::class, 'store'])->name('manager-edit-requests.store');
    Route::patch('/manager-edit-requests/{managerEditRequest}', [ManagerEditRequestController::class, 'handle'])->name('manager-edit-requests.handle');
    Route::get('/company-settings', [CompanySettingsController::class, 'edit'])->name('company-settings.edit');
    Route::patch('/company-settings', [CompanySettingsController::class, 'update'])->name('company-settings.update');
    Route::get('/company-applications', [CompanyApplicationController::class, 'index'])->name('company-applications.index');
    Route::get('/company-applications/{companyApplication}', [CompanyApplicationController::class, 'show'])->name('company-applications.show');
    Route::post('/company-applications/{companyApplication}/approve', [CompanyApplicationController::class, 'approve'])->name('company-applications.approve');
    Route::post('/company-applications/{companyApplication}/reject', [CompanyApplicationController::class, 'reject'])->name('company-applications.reject');
    Route::post('/company-applications/{companyApplication}/refresh-payment', [CompanyApplicationController::class, 'refreshPaymentStatus'])->name('company-applications.refresh-payment');
    Route::resource('companies', CompanyController::class)->except(['show']);

    // Direct Messaging (accessible to all roles)
    Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/create', [MessageController::class, 'create'])->name('messages.create');
    Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');
    Route::get('messages/{message}/reply', [MessageController::class, 'reply'])->name('messages.reply');
    Route::post('messages/{message}/acknowledge', [MessageController::class, 'acknowledge'])->name('messages.acknowledge');
});

// Protected Routes
Route::middleware(['auth', 'prevent-back-history', 'company.subscription.active', 'accountant.restrict'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');
    Route::get('/dashboard/recent-payments', [DashboardController::class, 'recentPayments'])->name('dashboard.recent-payments');
    Route::get('/dashboard/overdue-rents', [DashboardController::class, 'overdueRents'])->name('dashboard.overdue-rents');
    // Core Modules
    Route::get('rents/{rent}/agreement', [RentController::class, 'agreement'])->name('rents.agreement');
    Route::post('rents/{rent}/upload-agreement', [RentController::class, 'uploadAgreement'])->name('rents.upload-agreement');
    Route::get('rents/{rent}/view-uploaded-agreement', [RentController::class, 'viewUploadedAgreement'])->name('rents.view-uploaded-agreement');
    Route::get('rents/{rent}/preview-uploaded-agreement', [RentController::class, 'previewUploadedAgreement'])->name('rents.preview-uploaded-agreement');
    Route::get('rents/{rent}/download-agreement', [RentController::class, 'downloadAgreement'])->name('rents.download-agreement');
    Route::delete('rents/{rent}/delete-uploaded-agreement', [RentController::class, 'deleteUploadedAgreement'])->name('rents.delete-uploaded-agreement');

    // User Status Toggle
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('buildings', BuildingController::class);
    Route::post('buildings/{building}/announce', [BuildingController::class, 'announce'])->name('buildings.announce');
    Route::post('buildings/{building}/announcements/{announcement}/dismiss', [BuildingController::class, 'dismissAnnouncement'])->name('buildings.announcements.dismiss');
    Route::delete('buildings/{building}/announcements/{announcement}', [BuildingController::class, 'destroyAnnouncement'])->name('buildings.announcements.destroy');
    Route::resource('units', UnitController::class);
    Route::post('tenants/{tenant}/evict', [TenantController::class, 'evict'])->name('tenants.evict');
    Route::get('tenants/{tenant}/eviction-export', [TenantController::class, 'downloadEvictionExport'])->name('tenants.eviction-export');
    Route::post('tenants/{tenant}/reminder-settings', [TenantController::class, 'updateReminderSettings'])->name('tenants.reminder-settings');
    Route::resource('tenants', TenantController::class);
    Route::get('rents/expired-leases', [RentController::class, 'expiredLeases'])->name('rents.expired-leases');
    Route::resource('rents', RentController::class);
    Route::get('payments/{payment}/receipt', [PaymentController::class, 'downloadReceipt'])->name('payments.receipt');
    Route::resource('payments', PaymentController::class);
    Route::get('maintenance/{maintenance}/archive-download', [MaintenanceRequestController::class, 'downloadArchive'])->name('maintenance.archive-download');
    Route::resource('maintenance', MaintenanceRequestController::class);

    // New Accounting Module
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/', [AccountingController::class, 'index'])->name('index');
        Route::get('/report', [AccountingController::class, 'report'])->name('report');
        Route::get('/export', [AccountingController::class, 'export'])->name('export')->middleware('admin.export'); // CSV Export
        Route::get('/export-pdf', [AccountingController::class, 'exportPdf'])->name('export-pdf')->middleware('admin.export'); // PDF Export
        Route::post('/income', [AccountingController::class, 'storeIncome'])->name('store.income');
        Route::post('/expense', [AccountingController::class, 'storeExpense'])->name('store.expense');
        Route::patch('/entries/{entry}', [AccountingController::class, 'update'])->name('update');
        Route::patch('/entries/{entry}/finalize', [AccountingController::class, 'finalize'])->name('finalize');
        Route::patch('/entries/{entry}/reject', [AccountingController::class, 'reject'])->name('reject');
        Route::patch('/entries/{entry}/lock', [AccountingController::class, 'lock'])->name('lock');
        Route::patch('/entries/{entry}/unlock', [AccountingController::class, 'unlock'])->name('unlock');
        Route::post('/entries/{entry}/request-edit', [AccountingController::class, 'requestEdit'])->name('request-edit');
        Route::post('/edit-requests/{editRequest}/handle', [AccountingController::class, 'handleEditRequest'])->name('handle-edit-request');
        Route::get('/audit-trail', [AccountingController::class, 'auditTrail'])->name('audit-trail');
    });
});
