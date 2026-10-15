<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AccountingEntry;
use App\Models\Building;
use App\Models\ManagerEditRequest;
use App\Models\Payment;
use App\Models\User;
use App\Support\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BuildingController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (! $user->hasPermission('buildings.view')) {
                if ($request->routeIs('buildings.show') || $request->routeIs('buildings.announcements.dismiss')) {
                    $building = $request->route('building');
                    $tenant = optional($user)->tenant;
                    $tenantBuildingId = optional(optional($tenant)->unit)->building_id;
                    if ($building && $tenantBuildingId === optional($building)->id) {
                        return $next($request);
                    }
                }
                abort(403, 'Unauthorized action.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        if (auth()->user()->isManager() && Schema::hasColumn('buildings', 'manager_id')) {
            $buildings = Building::with(['creator'])->where('manager_id', auth()->id())->withCount(['units', 'activeUnits'])->paginate(10);
        } else {
            $buildings = Building::with(['creator'])->withCount(['units', 'activeUnits'])->paginate(10);
        }

        return view('buildings.index', compact('buildings'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('buildings.create'), 403);

        return view('buildings.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('buildings.create'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'address_line1' => 'required|string|max:200',
            'address_line2' => 'nullable|string|max:200',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'image' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:4096',
            'total_units' => 'required|integer|min:1',
            'total_floors' => 'required|integer|min:1',
        ]);

        if ($request->hasFile('image') && Schema::hasColumn('buildings', 'image_path')) {
            $imagePath = $request->file('image')->store('buildings', 'public');
            $validated['image_path'] = $imagePath;
        }

        $building = Building::create($validated);
        app(AuditLogService::class)->log('building_created', $building, null, $building->toArray());

        return redirect()->route('buildings.index')->with('success', 'Building created successfully.');
    }

    public function show(Building $building)
    {
        if (auth()->user()->isManager() && Schema::hasColumn('buildings', 'manager_id') && $building->manager_id !== auth()->id()) {
            abort(403);
        }
        $user = auth()->user();
        $currentYear = now()->year;
        $relations = ['units.currentTenant', 'tenants', 'rents.payments', 'rents.tenant', 'rents.unit', 'manager'];
        $hasLegacyExpensesTable = Schema::hasTable('legacy_expenses');
        $hasAccountingEntriesTable = Schema::hasTable('accounting_entries');
        if ($hasLegacyExpensesTable) {
            $relations[] = 'expenses';
        }
        $building->load($relations);
        $buildingTenants = $building->tenants
            ->where('active', true)
            ->unique('id')
            ->values();
        $buildingRents = $building->rents
            ->where('status', 'ACTIVE')
            ->sortByDesc('start_date')
            ->values();
        $buildingHistoryTenants = $building->tenants
            ->where('active', false)
            ->sortByDesc('move_out_date')
            ->values();
        $buildingHistoryRents = $building->rents
            ->filter(function ($rent) {
                return optional($rent->tenant)->active === false;
            })
            ->sortByDesc('start_date')
            ->values();

        $announcements = collect();
        if (Schema::hasTable('announcements')) {
            $announcements = Announcement::where('building_id', $building->id)
                ->whereDoesntHave('dismissedBy', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })
                ->latest()
                ->take(5)
                ->get();
        }

        if (Schema::hasTable('announcement_views') && $announcements->isNotEmpty()) {
            $viewRows = $announcements->map(function ($announcement) use ($user) {
                return [
                    'announcement_id' => $announcement->id,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            DB::table('announcement_views')->upsert(
                $viewRows,
                ['announcement_id', 'user_id'],
                ['updated_at']
            );
        }

        // Calculate stats
        $occupied_count = $building->units->where('status', 'OCCUPIED')->count();
        $maintenance_count = $building->units->where('status', 'MAINTENANCE')->count();
        $ytdRevenue = (float) Payment::where('status', 'COMPLETED')
            ->whereYear('payment_date', $currentYear)
            ->whereHas('rent.unit', function ($query) use ($building) {
                $query->where('building_id', $building->id);
            })
            ->sum('amount');
        $ytdExpenses = 0.0;

        if ($hasAccountingEntriesTable) {
            $ytdExpenses = (float) AccountingEntry::expense()
                ->notRejected()
                ->where('building_id', $building->id)
                ->whereYear('entry_date', $currentYear)
                ->sum('amount');
        } elseif ($hasLegacyExpensesTable) {
            $ytdExpenses = (float) $building->expenses
                ->filter(function ($expense) use ($currentYear) {
                    return optional($expense->expense_date)->year === $currentYear;
                })
                ->sum('amount');
        }

        $stats = [
            'total_units' => $building->total_units,
            'created_units' => $building->units->count(),
            'occupied_units' => $occupied_count,
            'maintenance_units' => $maintenance_count,
            'available_units' => max(0, $building->total_units - $occupied_count - $maintenance_count),
            'total_tenants' => $buildingTenants->count(),
            'ytd_total_revenue' => $ytdRevenue,
            'total_expenses' => $ytdExpenses,
            'total_income' => $ytdRevenue - $ytdExpenses,
        ];

        return view('buildings.show', compact(
            'building',
            'stats',
            'announcements',
            'buildingTenants',
            'buildingRents',
            'buildingHistoryTenants',
            'buildingHistoryRents'
        ));
    }

    public function edit(Building $building)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('buildings.edit'), 403);

        if ($user->isManager()) {
            abort_unless((int) $building->manager_id === (int) $user->id, 403, 'Unauthorized action.');
            abort_unless(
                ManagerEditRequest::hasActivePermissionFor($user->id, $building),
                403,
                ManagerEditRequest::denialMessageFor($user->id, $building)
            );
        } else {
            abort_unless($user->isAdmin(), 403, 'Managers cannot edit buildings directly. Request admin edit instead.');
        }

        $supportsManagerAssignment = Schema::hasColumn('buildings', 'manager_id');
        $managers = $supportsManagerAssignment
            ? User::query()
                ->where('company_id', $building->company_id)
                ->whereHas('role', function ($q) {
                    $q->where('name', 'manager');
                })
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
            : collect();

        return view('buildings.edit', compact('building', 'managers', 'supportsManagerAssignment'));
    }

    public function update(Request $request, Building $building)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('buildings.edit'), 403);

        if ($user->isManager()) {
            abort_unless((int) $building->manager_id === (int) $user->id, 403, 'Unauthorized action.');
            abort_unless(
                ManagerEditRequest::hasActivePermissionFor($user->id, $building),
                403,
                ManagerEditRequest::denialMessageFor($user->id, $building)
            );
        } else {
            abort_unless($user->isAdmin(), 403, 'Managers cannot edit buildings directly. Request admin edit instead.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'address_line1' => 'required|string|max:200',
            'address_line2' => 'nullable|string|max:200',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'image' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:4096',
            'total_units' => 'required|integer|min:1',
            'total_floors' => 'required|integer|min:1',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        if ($request->hasFile('image') && Schema::hasColumn('buildings', 'image_path')) {
            $imagePath = $request->file('image')->store('buildings', 'public');
            $validated['image_path'] = $imagePath;
        }

        if (! Schema::hasColumn('buildings', 'manager_id')) {
            if ($request->filled('manager_id')) {
                return back()
                    ->withInput()
                    ->withErrors(['manager_id' => 'Manager assignment is not available yet on this server. Run the latest building manager migration first.']);
            }

            unset($validated['manager_id']);
        }

        if ($user->isManager()) {
            unset($validated['manager_id']);
        }

        if (array_key_exists('manager_id', $validated) && filled($validated['manager_id'])) {
            $managerIsAssignable = User::query()
                ->whereKey($validated['manager_id'])
                ->where('company_id', $building->company_id)
                ->where('is_active', true)
                ->whereHas('role', function ($query) {
                    $query->where('name', 'manager');
                })
                ->exists();

            if (! $managerIsAssignable) {
                return back()
                    ->withInput()
                    ->withErrors(['manager_id' => 'Assign an active manager from the same company as this building.']);
            }
        }

        $oldValues = $building->toArray();
        $building->update($validated);
        app(AuditLogService::class)->log('building_updated', $building, $oldValues, $building->toArray());

        return redirect()->route('buildings.index')->with('success', 'Building updated successfully.');
    }

    public function announce(Request $request, Building $building)
    {
        if (! auth()->user()->hasPermission('buildings.edit')) {
            abort(403);
        }
        if (auth()->user()->isManager() && Schema::hasColumn('buildings', 'manager_id') && $building->manager_id !== auth()->id()) {
            abort(403);
        }
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'content' => 'required|string|max:5000',
        ]);
        Announcement::create([
            'building_id' => $building->id,
            'manager_id' => auth()->id(),
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        return back()->with('success', 'Announcement posted.');
    }

    public function dismissAnnouncement(Building $building, Announcement $announcement)
    {
        $user = auth()->user();
        if ($announcement->building_id !== $building->id) {
            abort(404);
        }

        $allowed = false;

        if ($user->isTenant()) {
            $tenant = optional($user)->tenant;
            $tenantBuildingId = optional(optional($tenant)->unit)->building_id;
            if ($tenantBuildingId === $building->id) {
                $allowed = true;
            }
        } elseif ($user->isManager()) {
            if (Schema::hasColumn('buildings', 'manager_id')) {
                if ($building->manager_id === $user->id) {
                    $allowed = true;
                }
            } else {
                $allowed = true;
            }
        } elseif ($user->isAdmin()) {
            $allowed = true;
        }

        if (! $allowed) {
            abort(403);
        }

        DB::table('announcement_dismissals')->updateOrInsert(
            ['announcement_id' => $announcement->id, 'user_id' => $user->id],
            ['updated_at' => now(), 'created_at' => now()]
        );

        return back()->with('success', 'Announcement removed from your view.');
    }

    public function destroyAnnouncement(Building $building, Announcement $announcement)
    {
        $user = auth()->user();
        if (! ($user->hasPermission('buildings.delete') || ($user->isManager() && Schema::hasColumn('buildings', 'manager_id') && $building->manager_id === $user->id))) {
            abort(403);
        }
        if ($announcement->building_id !== $building->id) {
            abort(404);
        }
        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    public function destroy(Building $building)
    {
        abort_unless(auth()->user()->hasPermission('buildings.delete'), 403);

        if ($building->units()->exists()) {
            return back()->with('error', 'Cannot delete building with existing units. Delete units first.');
        }

        app(AuditLogService::class)->log('building_deleted', $building, $building->toArray(), null);
        $building->delete();

        return redirect()->route('buildings.index')->with('success', 'Building deleted successfully.');
    }
}
