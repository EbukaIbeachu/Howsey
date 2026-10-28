<?php

namespace App\Http\Controllers;

use App\Models\AccountingEntry;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Company;
use App\Models\CompanyApplication;
use App\Models\Payment;
use App\Models\Rent;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isAccountant()) {
            return redirect()->route('accounting.index');
        }

        if ($user->isSuperAdmin()) {
            return $this->platformDashboard();
        }

        if ($user->isAdmin() || $user->isManager()) {
            return $this->adminDashboard();
        }

        if ($user->isTenant()) {
            return $this->tenantDashboard();
        }

        return redirect()->route('login');
    }

    private function platformDashboard()
    {
        $selectedCompany = app(CurrentCompany::class)->get();
        if ($selectedCompany) {
            $selectedCompany->loadMissing('settings')->loadCount(['users', 'buildings']);
        }

        $companies = Company::query()
            ->where('is_demo', false)
            ->with(['settings'])
            ->withCount(['users', 'buildings'])
            ->latest()
            ->take(8)
            ->get();

        $hasCompanyApplicationsTable = Schema::hasTable('company_applications');

        $stats = [
            'building_count' => Building::withoutGlobalScopes()
                ->whereHas('company', function ($query) {
                    $query->where('is_demo', false);
                })
                ->count(),
            'manager_count' => User::query()
                ->where('is_active', true)
                ->whereNotNull('company_id')
                ->whereHas('company', function ($query) {
                    $query->where('is_demo', false);
                })
                ->whereHas('role', function ($query) {
                    $query->whereRaw('LOWER(name) = ?', ['manager']);
                })
                ->count(),
            'accountant_count' => User::query()
                ->where('is_active', true)
                ->whereNotNull('company_id')
                ->whereHas('company', function ($query) {
                    $query->where('is_demo', false);
                })
                ->whereHas('role', function ($query) {
                    $query->whereRaw('LOWER(name) = ?', ['accountant']);
                })
                ->count(),
            'tenant_count' => Tenant::withoutGlobalScopes()
                ->where('active', true)
                ->whereHas('company', function ($query) {
                    $query->where('is_demo', false);
                })
                ->count(),
            'user_log_count' => AuditLog::withoutGlobalScopes()
                ->whereNotNull('company_id')
                ->whereHas('company', function ($query) {
                    $query->where('is_demo', false);
                })
                ->count(),
        ];

        $recentApplications = $hasCompanyApplicationsTable
            ? CompanyApplication::query()->latest()->take(6)->get()
            : collect();

        return view('admin.platform-dashboard', [
            'stats' => $stats,
            'selectedCompany' => $selectedCompany,
            'recentCompanies' => $companies,
            'recentApplications' => $recentApplications,
        ]);
    }

    public function recentPayments()
    {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            return redirect()
                ->route('dashboard')
                ->with('info', 'Super admin financial activity views are hidden on the HOWSEY platform dashboard.');
        }

        $isManager = $user->isManager();
        $buildingIds = $this->getManagedBuildingIds($user, $isManager);

        $paymentsQuery = $this->recentPaymentsQuery($isManager, $buildingIds);
        if ($user->isTenant()) {
            $paymentsQuery->whereHas('rent.tenant', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }
        $recentPayments = $paymentsQuery->paginate(15);

        return view('dashboard.recent-payments', compact('recentPayments'));
    }

    public function overdueRents()
    {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            return redirect()
                ->route('dashboard')
                ->with('info', 'Super admin financial activity views are hidden on the HOWSEY platform dashboard.');
        }

        $isManager = $user->isManager();
        $buildingIds = $this->getManagedBuildingIds($user, $isManager);
        $overdueRents = $this->overdueRentsCollection($isManager, $buildingIds, $user);

        return view('dashboard.overdue-rents', compact('overdueRents'));
    }

    public function analytics()
    {
        $user = Auth::user();

        if (! $user->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($user->isSuperAdmin()) {
            return redirect()
                ->route('dashboard')
                ->with('info', 'Super admin analytics is limited to platform-safe counts only.');
        }

        $isManager = $user->isManager();
        $buildingIds = $this->getManagedBuildingIds($user, $isManager);

        return view('dashboard.analytics', $this->buildAnalyticsViewData($isManager, $buildingIds));
    }

    private function adminDashboard()
    {
        $user = Auth::user();
        $isManager = $user->isManager();
        $buildingIds = $this->getManagedBuildingIds($user, $isManager);
        $dashboardAnnouncements = collect();
        $hasAnnouncementsTable = Schema::hasTable('announcements');
        $currentYear = now()->year;
        $platformCompanyCount = $user->can('viewAny', Company::class) ? Company::count() : null;

        // Statistics
        if ($isManager) {
            $paidTotal = (float) Payment::where('status', 'COMPLETED')
                ->whereYear('payment_date', $currentYear)
                ->whereHas('rent.unit.building', function ($q) use ($buildingIds) {
                    $q->whereIn('id', $buildingIds);
                })->sum('amount');
            $otherIncomeTotal = (float) AccountingEntry::income()
                ->notRejected()
                ->whereYear('entry_date', $currentYear)
                ->whereIn('building_id', $buildingIds)
                ->sum('amount');
            $expenseTotal = (float) AccountingEntry::expense()
                ->notRejected()
                ->whereYear('entry_date', $currentYear)
                ->whereIn('building_id', $buildingIds)
                ->sum('amount');

            $stats = [
                'total_buildings' => Building::whereIn('id', $buildingIds)->count(),
                'total_tenants' => Tenant::where('active', true)
                    ->whereHas('unit.building', function ($q) use ($buildingIds) {
                        $q->whereIn('id', $buildingIds);
                    })->count(),
                'paid_total' => $paidTotal,
                'net_cash_flow' => ($paidTotal + $otherIncomeTotal) - $expenseTotal,
                'overdue_count' => Rent::active()
                    ->whereHas('unit.building', function ($q) use ($buildingIds) {
                        $q->whereIn('id', $buildingIds);
                    })->get()->filter(function ($rent) {
                        return $rent->balance > 0;
                    })->count(),
            ];
            if ($hasAnnouncementsTable) {
                $dashboardAnnouncementsQuery = Announcement::whereIn('building_id', $buildingIds)
                    ->whereDoesntHave('dismissedBy', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    });

                if (Schema::hasTable('announcement_views')) {
                    $dashboardAnnouncementsQuery->whereDoesntHave('viewedBy', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    });
                }

                $dashboardAnnouncements = $dashboardAnnouncementsQuery
                    ->latest()
                    ->take(5)
                    ->get();
            }
        } else {
            $paidTotal = (float) Payment::where('status', 'COMPLETED')
                ->whereYear('payment_date', $currentYear)
                ->sum('amount');
            $otherIncomeTotal = (float) AccountingEntry::income()
                ->notRejected()
                ->whereYear('entry_date', $currentYear)
                ->sum('amount');
            $expenseTotal = (float) AccountingEntry::expense()
                ->notRejected()
                ->whereYear('entry_date', $currentYear)
                ->sum('amount');

            $stats = [
                'total_buildings' => Building::count(),
                'total_tenants' => Tenant::where('active', true)->count(),
                'paid_total' => $paidTotal,
                'net_cash_flow' => ($paidTotal + $otherIncomeTotal) - $expenseTotal,
                'overdue_count' => Rent::active()->get()->filter(function ($rent) {
                    return $rent->balance > 0;
                })->count(),
            ];

            if ($hasAnnouncementsTable) {
                $dashboardAnnouncementsQuery = Announcement::whereDoesntHave('dismissedBy', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });

                if (Schema::hasTable('announcement_views')) {
                    $dashboardAnnouncementsQuery->whereDoesntHave('viewedBy', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    });
                }

                $dashboardAnnouncements = $dashboardAnnouncementsQuery
                    ->latest()
                    ->take(10)
                    ->get();
            }
        }

        $revenueSeries = $this->topBuildingsRevenueSeries($isManager, $buildingIds);
        $chartLabels = $revenueSeries['labels'];
        $chartDatasets = $revenueSeries['datasets'];

        // Recent Payments
        $recentPayments = $this->recentPaymentsQuery($isManager, $buildingIds)
            ->take(10)
            ->get();

        // Overdue Rents (Simplified logic for example)
        $overdueRents = $this->overdueRentsCollection($isManager, $buildingIds);

        // Pending Requests (Placeholder)
        $requests = [];

        return view('admin.dashboard', compact('stats', 'recentPayments', 'overdueRents', 'requests', 'chartLabels', 'chartDatasets', 'dashboardAnnouncements', 'platformCompanyCount'));
    }

    private function tenantDashboard()
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if (! $tenant) {
            // Check for existing tenant profile by email first (in case it wasn't linked during registration)
            if ($user->isTenant()) {
                $existingTenant = Tenant::where('email', $user->email)->first();

                if ($existingTenant) {
                    $existingTenant->update(['user_id' => $user->id]);
                    $tenant = $existingTenant;
                }
            }
        }

        if ($tenant) {
            $tenant->loadMissing([
                'unit.building.manager',
                'rents.payments',
                'currentRent.unit.building.manager',
            ]);
            $rent = $tenant->currentRent ?? $tenant->rents()->active()->latest()->first();
            $payments = $tenant->payments()->latest('payment_date')->take(5)->get();
            $buildingId = $rent ? optional($rent->unit)->building_id : optional(optional($tenant->unit))->building_id;
            $buildingManager = $rent
                ? optional(optional($rent->unit)->building)->manager
                : optional(optional(optional($tenant->unit)->building))->manager;
            $totalPaid = $tenant->rents->sum(function ($rentRecord) {
                return (float) $rentRecord->total_paid;
            });
            $outstandingBalance = $tenant->rents->sum(function ($rentRecord) {
                return (float) $rentRecord->balance;
            });
            if ($buildingId) {
                $announcementsQuery = Announcement::where('building_id', $buildingId)
                    ->whereDoesntHave('dismissedBy', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    });

                if (\Illuminate\Support\Facades\Schema::hasTable('announcement_views')) {
                    $announcementsQuery->whereDoesntHave('viewedBy', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    });
                }

                $announcements = $announcementsQuery
                    ->latest()
                    ->take(5)
                    ->get();
            } else {
                $announcements = collect([]);
            }
        } else {
            $rent = null;
            $payments = collect([]);
            $announcements = collect([]);
            $buildingManager = null;
            $totalPaid = 0;
            $outstandingBalance = 0;
        }

        return view('tenant.dashboard', compact('tenant', 'rent', 'payments', 'announcements', 'buildingManager', 'totalPaid', 'outstandingBalance'));
    }

    private function getManagedBuildingIds($user, bool $isManager)
    {
        return $isManager ? Building::where('manager_id', $user->id)->pluck('id') : null;
    }

    private function buildAnalyticsViewData(bool $isManager, $buildingIds): array
    {
        $now = now();
        $currentYear = $now->year;
        $yearRangeStart = $currentYear - 4;
        $lastSixMonthsStart = $now->copy()->subMonths(5)->startOfMonth();
        $scopedBuildings = $this->scopedBuildingsQuery($isManager, $buildingIds)
            ->with(['units.currentTenant'])
            ->orderBy('name')
            ->get();

        $buildingRevenueMap = $this->scopedPaymentsQuery($isManager, $buildingIds)
            ->join('rents', 'payments.rent_id', '=', 'rents.id')
            ->join('units', 'rents.unit_id', '=', 'units.id')
            ->selectRaw('units.building_id as building_id, SUM(payments.amount) as total')
            ->groupBy('units.building_id')
            ->pluck('total', 'building_id');

        $buildingLabels = [];
        $buildingRevenueData = [];
        $occupancyLabels = [];
        $occupancyRateData = [];
        $tenantDistributionLabels = [];
        $tenantDistributionData = [];

        foreach ($scopedBuildings as $building) {
            $buildingLabels[] = $building->name;
            $buildingRevenueData[] = (float) ($buildingRevenueMap[$building->id] ?? 0);

            $configuredTotalUnits = (int) ($building->total_units ?? 0);
            $registeredUnitCount = $building->units->count();
            $totalUnits = $configuredTotalUnits > 0 ? $configuredTotalUnits : $registeredUnitCount;
            $activeTenantCount = $building->units->filter(function ($unit) {
                return $unit->currentTenant !== null;
            })->count();
            $occupiedUnits = min($activeTenantCount, $totalUnits);

            $occupancyLabels[] = $building->name;
            $occupancyRateData[] = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

            $tenantDistributionLabels[] = $building->name;
            $tenantDistributionData[] = $activeTenantCount;
        }

        $monthlyRevenue = $this->scopedPaymentsQuery($isManager, $buildingIds)
            ->whereYear('payment_date', $currentYear)
            ->selectRaw('MONTH(payment_date) as month_number, SUM(amount) as total')
            ->groupBy('month_number')
            ->orderBy('month_number')
            ->pluck('total', 'month_number');

        $monthlyLabels = [];
        $monthlyRevenueData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyLabels[] = $now->copy()->month($month)->format('M');
            $monthlyRevenueData[] = (float) ($monthlyRevenue[$month] ?? 0);
        }

        $yearlyRevenue = $this->scopedPaymentsQuery($isManager, $buildingIds)
            ->whereYear('payment_date', '>=', $yearRangeStart)
            ->selectRaw('YEAR(payment_date) as year_number, SUM(amount) as total')
            ->groupBy('year_number')
            ->orderBy('year_number')
            ->pluck('total', 'year_number');

        $yearlyLabels = [];
        $yearlyRevenueData = [];
        for ($year = $yearRangeStart; $year <= $currentYear; $year++) {
            $yearlyLabels[] = (string) $year;
            $yearlyRevenueData[] = (float) ($yearlyRevenue[$year] ?? 0);
        }

        $paymentMethodBreakdown = $this->scopedPaymentsQuery($isManager, $buildingIds)
            ->selectRaw("COALESCE(NULLIF(payment_method, ''), 'Unspecified') as payment_method_group, SUM(amount) as total, COUNT(*) as payment_count")
            ->groupBy('payment_method_group')
            ->orderByDesc('total')
            ->get();

        $paymentMethodLabels = $paymentMethodBreakdown->pluck('payment_method_group')->all();
        $paymentMethodAmountData = $paymentMethodBreakdown->map(function ($row) {
            return (float) $row->total;
        })->all();
        $paymentMethodCountData = $paymentMethodBreakdown->map(function ($row) {
            return (int) $row->payment_count;
        })->all();

        $overdueByBuilding = $this->overdueRentsCollection($isManager, $buildingIds)
            ->groupBy(function ($rent) {
                return optional(optional($rent->unit)->building)->id;
            })
            ->map(function ($rents) {
                return (float) $rents->sum(function ($rent) {
                    return max((float) $rent->balance, 0);
                });
            });

        $overdueLabels = [];
        $overdueBalanceData = [];
        foreach ($scopedBuildings as $building) {
            $overdueLabels[] = $building->name;
            $overdueBalanceData[] = (float) ($overdueByBuilding[$building->id] ?? 0);
        }

        $otherIncomeByMonth = $this->scopedAccountingEntriesQuery($isManager, $buildingIds)
            ->income()
            ->whereYear('entry_date', $currentYear)
            ->selectRaw('MONTH(entry_date) as month_number, SUM(amount) as total')
            ->groupBy('month_number')
            ->orderBy('month_number')
            ->pluck('total', 'month_number');

        $expenseByMonth = $this->scopedAccountingEntriesQuery($isManager, $buildingIds)
            ->expense()
            ->whereYear('entry_date', $currentYear)
            ->selectRaw('MONTH(entry_date) as month_number, SUM(amount) as total')
            ->groupBy('month_number')
            ->orderBy('month_number')
            ->pluck('total', 'month_number');

        $incomeExpenseLabels = $monthlyLabels;
        $incomeTotalsData = [];
        $expenseTotalsData = [];
        for ($month = 1; $month <= 12; $month++) {
            $incomeTotalsData[] = (float) ($monthlyRevenue[$month] ?? 0) + (float) ($otherIncomeByMonth[$month] ?? 0);
            $expenseTotalsData[] = (float) ($expenseByMonth[$month] ?? 0);
        }

        $lastSixMonthsIncomeTotal = (float) $this->scopedPaymentsQuery($isManager, $buildingIds)
            ->where('payment_date', '>=', $lastSixMonthsStart)
            ->sum('amount')
            + (float) $this->scopedAccountingEntriesQuery($isManager, $buildingIds)
                ->income()
                ->where('entry_date', '>=', $lastSixMonthsStart)
                ->sum('amount');

        $lastSixMonthsExpenseTotal = (float) $this->scopedAccountingEntriesQuery($isManager, $buildingIds)
            ->expense()
            ->where('entry_date', '>=', $lastSixMonthsStart)
            ->sum('amount');

        $buildingChart = $this->normalizeSingleSeries($buildingLabels, $buildingRevenueData);
        $paymentMethodChart = $this->normalizeSingleSeries($paymentMethodLabels, $paymentMethodAmountData);
        $overdueChart = $this->normalizeSingleSeries($overdueLabels, $overdueBalanceData);
        $occupancyChart = $this->normalizeSingleSeries($occupancyLabels, $occupancyRateData);
        $tenantDistributionChart = $this->normalizeSingleSeries($tenantDistributionLabels, $tenantDistributionData);

        return [
            'analyticsSummary' => [
                'scope_label' => $isManager ? 'Your assigned buildings' : 'All buildings',
                'building_count' => $scopedBuildings->count(),
                'payment_count' => (int) array_sum($paymentMethodCountData),
                'last_six_months_income_total' => $lastSixMonthsIncomeTotal,
                'last_six_months_expense_total' => $lastSixMonthsExpenseTotal,
            ],
            'buildingChart' => $buildingChart,
            'monthlyTrendChart' => [
                'labels' => $monthlyLabels,
                'data' => $monthlyRevenueData,
            ],
            'yearlyTrendChart' => [
                'labels' => $yearlyLabels,
                'data' => $yearlyRevenueData,
            ],
            'paymentMethodChart' => [
                'labels' => $paymentMethodChart['labels'],
                'amounts' => $paymentMethodChart['data'],
                'counts' => $this->normalizeToLabelSet($paymentMethodChart['labels'], $paymentMethodLabels, $paymentMethodCountData),
            ],
            'overdueChart' => $overdueChart,
            'occupancyChart' => $occupancyChart,
            'tenantDistributionChart' => $tenantDistributionChart,
            'incomeExpenseChart' => [
                'labels' => $incomeExpenseLabels,
                'income' => $incomeTotalsData,
                'expenses' => $expenseTotalsData,
            ],
            'analyticsInsights' => $this->buildAnalyticsInsights(
                $buildingChart['labels'],
                $buildingChart['data'],
                $overdueChart['labels'],
                $overdueChart['data'],
                $occupancyChart['labels'],
                $occupancyChart['data'],
                $paymentMethodChart['labels'],
                $paymentMethodChart['data']
            ),
        ];
    }

    private function recentPaymentsQuery(bool $isManager, $buildingIds)
    {
        $query = Payment::with(['rent.tenant', 'rent.unit.building'])
            ->where('status', 'COMPLETED')
            ->latest('payment_date');

        if ($isManager) {
            $query->whereHas('rent.unit.building', function ($q) use ($buildingIds) {
                $q->whereIn('id', $buildingIds);
            });
        }

        return $query;
    }

    private function overdueRentsCollection(bool $isManager, $buildingIds, $user = null)
    {
        $query = Rent::active()
            ->with(['tenant', 'unit.building']);

        if ($isManager) {
            $query->whereHas('unit.building', function ($q) use ($buildingIds) {
                $q->whereIn('id', $buildingIds);
            });
        }

        if ($user && $user->isTenant()) {
            $query->whereHas('tenant', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query->get()->filter(function ($rent) {
            return $rent->balance > 0;
        })->values();
    }

    private function scopedBuildingsQuery(bool $isManager, $buildingIds)
    {
        $query = Building::query();

        if ($isManager) {
            $query->whereIn('id', $buildingIds);
        }

        return $query;
    }

    private function scopedPaymentsQuery(bool $isManager, $buildingIds)
    {
        $query = Payment::query()->where('payments.status', 'COMPLETED');

        if ($isManager) {
            $query->whereHas('rent.unit.building', function ($q) use ($buildingIds) {
                $q->whereIn('id', $buildingIds);
            });
        }

        return $query;
    }

    private function scopedAccountingEntriesQuery(bool $isManager, $buildingIds)
    {
        $query = AccountingEntry::query()->notRejected();

        if ($isManager) {
            $query->whereIn('building_id', $buildingIds);
        }

        return $query;
    }

    /**
     * Builds one six-month revenue line per building so buildings can be
     * compared against each other on the dashboard chart. Only the highest
     * earning buildings are returned, to keep the chart readable.
     */
    private function topBuildingsRevenueSeries(bool $isManager, $buildingIds, int $limit = 5): array
    {
        $startMonth = now()->subMonths(5)->startOfMonth();

        $labels = [];
        $monthKeys = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKeys[] = $date->format('Y-m');
            $labels[] = $date->format('M Y');
        }

        $query = Payment::query()
            ->join('rents', 'payments.rent_id', '=', 'rents.id')
            ->join('units', 'rents.unit_id', '=', 'units.id')
            ->join('buildings', 'units.building_id', '=', 'buildings.id')
            ->where('payments.status', 'COMPLETED')
            ->where('payments.payment_date', '>=', $startMonth);

        if ($isManager) {
            $query->whereIn('units.building_id', $buildingIds);
        }

        $rows = $query
            ->selectRaw('buildings.id as building_id, buildings.name as building_name, DATE_FORMAT(payments.payment_date, "%Y-%m") as month_key, SUM(payments.amount) as total')
            ->groupBy('buildings.id', 'buildings.name', 'month_key')
            ->get();

        $byBuilding = [];
        foreach ($rows as $row) {
            $buildingId = (int) $row->building_id;
            if (! isset($byBuilding[$buildingId])) {
                $byBuilding[$buildingId] = [
                    'name' => (string) $row->building_name,
                    'months' => [],
                    'total' => 0.0,
                ];
            }

            $amount = (float) $row->total;
            $byBuilding[$buildingId]['months'][(string) $row->month_key] = $amount;
            $byBuilding[$buildingId]['total'] += $amount;
        }

        uasort($byBuilding, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        $datasets = [];
        foreach (array_slice($byBuilding, 0, $limit, true) as $building) {
            $data = [];
            foreach ($monthKeys as $monthKey) {
                $data[] = (float) ($building['months'][$monthKey] ?? 0);
            }

            $datasets[] = [
                'label' => $building['name'],
                'data' => $data,
            ];
        }

        if (empty($datasets)) {
            $datasets[] = [
                'label' => 'No Revenue Data',
                'data' => array_fill(0, count($labels), 0),
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    private function normalizeSingleSeries(array $labels, array $data, string $emptyLabel = 'No Data Yet'): array
    {
        if (count($labels) === 0) {
            return [
                'labels' => [$emptyLabel],
                'data' => [0],
            ];
        }

        return [
            'labels' => array_values($labels),
            'data' => array_values($data),
        ];
    }

    private function normalizeToLabelSet(array $normalizedLabels, array $sourceLabels, array $sourceData): array
    {
        if ($normalizedLabels === ['No Data Yet']) {
            return [0];
        }

        $dataMap = array_combine($sourceLabels, $sourceData) ?: [];

        return array_map(function ($label) use ($dataMap) {
            return (float) ($dataMap[$label] ?? 0);
        }, $normalizedLabels);
    }

    private function buildAnalyticsInsights(
        array $buildingLabels,
        array $buildingData,
        array $overdueLabels,
        array $overdueData,
        array $occupancyLabels,
        array $occupancyData,
        array $paymentMethodLabels,
        array $paymentMethodData
    ): array {
        $insights = [];

        if ($buildingLabels !== ['No Data Yet']) {
            $maxRevenueIndex = array_keys($buildingData, max($buildingData))[0];
            $insights[] = $buildingLabels[$maxRevenueIndex].' currently leads revenue collection at '.number_format((float) $buildingData[$maxRevenueIndex], 2).' NGN.';
        } else {
            $insights[] = 'Revenue analytics will appear here after completed payments are recorded.';
        }

        if ($overdueLabels !== ['No Data Yet']) {
            $maxOverdueIndex = array_keys($overdueData, max($overdueData))[0];
            $insights[] = $overdueLabels[$maxOverdueIndex].' carries the highest overdue exposure at '.number_format((float) $overdueData[$maxOverdueIndex], 2).' NGN.';
        }

        if ($occupancyLabels !== ['No Data Yet']) {
            $maxOccupancyIndex = array_keys($occupancyData, max($occupancyData))[0];
            $insights[] = $occupancyLabels[$maxOccupancyIndex].' has the strongest occupancy rate at '.number_format((float) $occupancyData[$maxOccupancyIndex], 1).'%.';
        }

        if ($paymentMethodLabels !== ['No Data Yet']) {
            $topMethodIndex = array_keys($paymentMethodData, max($paymentMethodData))[0];
            $insights[] = $paymentMethodLabels[$topMethodIndex].' is the most valuable payment channel by collected amount.';
        }

        return $insights;
    }
}
