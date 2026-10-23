<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Building;
use App\Models\Tenant;
use App\Support\BrandingService;
use App\Support\CurrentCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentCompany::class, fn () => new CurrentCompany());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production to match APP_URL scheme
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '') {
            // Local EasyPHP runs the app through the /housing wrapper, so force the
            // full configured root URL to keep redirects and route() links in-app.
            URL::forceRootUrl($appUrl);
        }

        Schema::defaultStringLength(191);

        // Register MySQL-compatible functions for SQLite
        if (config('database.default') === 'sqlite') {
            try {
                $pdo = DB::connection()->getPdo();
                $pdo->sqliteCreateFunction('DATE_FORMAT', function ($date, $format) {
                    if (!$date) return null;
                    $timestamp = strtotime($date);
                    if ($timestamp === false) return $date;
                    $map = [
                        '%Y' => 'Y', '%y' => 'y', '%m' => 'm', '%c' => 'n',
                        '%d' => 'd', '%e' => 'j', '%H' => 'H', '%h' => 'h',
                        '%i' => 'i', '%s' => 's', '%M' => 'F', '%b' => 'M',
                        '%W' => 'l', '%w' => 'w', '%p' => 'A', '%r' => 'h:i:s A',
                        '%T' => 'H:i:s', '%D' => 'jS',
                    ];
                    $phpFormat = strtr($format, $map);
                    return date($phpFormat, $timestamp);
                });
                $pdo->sqliteCreateFunction('MONTH', function ($date) {
                    if (!$date) return null;
                    $timestamp = strtotime($date);
                    if ($timestamp === false) return null;
                    return (int) date('n', $timestamp);
                });
                $pdo->sqliteCreateFunction('YEAR', function ($date) {
                    if (!$date) return null;
                    $timestamp = strtotime($date);
                    if ($timestamp === false) return null;
                    return (int) date('Y', $timestamp);
                });
            } catch (\Throwable $e) {
                // Ignore if not SQLite
            }
        }

        Blade::if('permission', function (string $permission) {
            return Auth::check() && Auth::user()->hasPermission($permission);
        });

        $hasAnnouncementsTable = Schema::hasTable('announcements');
        $hasNotificationsTable = Schema::hasTable('notifications');
        $hasAnnouncementViewsTable = Schema::hasTable('announcement_views');

        View::composer('*', function ($view) use ($hasAnnouncementsTable, $hasNotificationsTable, $hasAnnouncementViewsTable) {
            static $sharedPayload = null;

            if ($sharedPayload !== null) {
                $view->with($sharedPayload);
                return;
            }

            $count = 0;
            $notificationCount = 0;
            $notifications = collect();
            $announcementUrl = null;
            $currentCompany = app(CurrentCompany::class)->get();
            $brandingCompany = Auth::check() && Auth::user()->isSuperAdmin()
                ? null
                : $currentCompany;
            $branding = app(BrandingService::class)->palette($brandingCompany);

            if (! $hasAnnouncementsTable) {
                $sharedPayload = [
                    'globalAnnouncementCount' => $count,
                    'globalAnnouncementUrl' => $announcementUrl,
                    'globalNotificationCount' => $notificationCount,
                    'globalUnreadNotifications' => $notifications,
                    'branding' => $branding,
                ];
                $view->with($sharedPayload);
                return;
            }

            if (Auth::check()) {
                $user = Auth::user();

                if ($user->isSuperAdmin()) {
                    if ($hasNotificationsTable) {
                        $superAdminNotificationsQuery = $user->unreadNotifications()
                            ->where('data->request_type', 'company_application');

                        $notificationCount = (clone $superAdminNotificationsQuery)->count();
                        $notifications = (clone $superAdminNotificationsQuery)
                            ->latest()
                            ->take(5)
                            ->get();
                    }
                } else {
                    if ($hasNotificationsTable) {
                        $notificationCount = $user->unreadNotifications()->count();
                        $notifications = $user->unreadNotifications()
                            ->latest()
                            ->take(5)
                            ->get();
                    }
                }

                if ($user->isSuperAdmin()) {
                    $count = 0;
                    $announcementUrl = null;
                } elseif ($user->isAdmin()) {
                    $announcementQuery = Announcement::whereDoesntHave('dismissedBy', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    });

                    if ($hasAnnouncementViewsTable) {
                        $announcementQuery->whereDoesntHave('viewedBy', function ($q) use ($user) {
                            $q->where('users.id', $user->id);
                        });
                    }

                    $count = $announcementQuery->count();
                    $announcementUrl = route('dashboard') . '#manager-announcements-section';
                } elseif ($user->isManager()) {
                    $buildingIds = Building::where('manager_id', $user->id)->pluck('id');
                    if ($buildingIds->isNotEmpty()) {
                        $announcementQuery = Announcement::whereIn('building_id', $buildingIds)
                            ->whereDoesntHave('dismissedBy', function ($q) use ($user) {
                                $q->where('users.id', $user->id);
                            });

                        if ($hasAnnouncementViewsTable) {
                            $announcementQuery->whereDoesntHave('viewedBy', function ($q) use ($user) {
                                $q->where('users.id', $user->id);
                            });
                        }

                        $count = $announcementQuery->count();
                    }

                    $announcementUrl = route('dashboard') . '#manager-announcements-section';
                } elseif ($user->isTenant()) {
                    $tenant = $user->tenant;
                    if (! $tenant) {
                        $tenant = Tenant::where('email', $user->email)->first();
                    }
                    if ($tenant) {
                        $rent = $tenant->currentRent ?? $tenant->rents()->active()->latest()->first();
                        $buildingId = $rent ? optional($rent->unit)->building_id : optional(optional($tenant->unit))->building_id;
                        if ($buildingId) {
                            $announcementQuery = Announcement::where('building_id', $buildingId)
                                ->whereDoesntHave('dismissedBy', function ($q) use ($user) {
                                    $q->where('users.id', $user->id);
                                });

                            if ($hasAnnouncementViewsTable) {
                                $announcementQuery->whereDoesntHave('viewedBy', function ($q) use ($user) {
                                    $q->where('users.id', $user->id);
                                });
                            }

                            $count = $announcementQuery->count();
                        }
                    }

                    $announcementUrl = route('dashboard') . '#announcements';
                }
            }

            $sharedPayload = [
                'globalAnnouncementCount' => $count,
                'globalAnnouncementUrl' => $announcementUrl,
                'globalNotificationCount' => $notificationCount,
                'globalUnreadNotifications' => $notifications,
                'branding' => $branding,
            ];

            $view->with($sharedPayload);
        });
    }
}
