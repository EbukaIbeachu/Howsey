<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\ManagerEditRequest;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\RentReminderService;
use App\Support\ArchivePathService;
use App\Support\AuditLogService;
use App\Support\AutoEmailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class TenantController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->hasPermission('tenants.view')) {
                abort(403, 'Unauthorized action.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $query = Tenant::with(['unit.building', 'user', 'creator']);
        if (auth()->user()->isManager()) {
            $query->whereHas('unit.building', function ($q) {
                $q->where('manager_id', auth()->id());
            });
        }
        $tenants = $query->paginate(15);

        return view('tenants.index', compact('tenants'));
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('tenants.create'), 403);

        $buildingsQuery = Building::withCount([
            'units',
            'units as occupied_units_count' => function ($query) {
                $query->where('status', 'OCCUPIED');
            },
        ]);
        $unitsQuery = Unit::with('building')->orderBy('building_id')->orderBy('unit_number');

        if (auth()->user()->isManager()) {
            $buildingsQuery->where('manager_id', auth()->id());
            $unitsQuery->whereHas('building', function ($query) {
                $query->where('manager_id', auth()->id());
            });
        }

        $buildings = $buildingsQuery->get();
        $units = $unitsQuery->get();
        $selectedUnitId = $request->query('unit_id');
        $selectedBuildingId = $request->query('building_id');

        if ($selectedUnitId) {
            $selectedUnit = $units->firstWhere('id', (int) $selectedUnitId);
            if ($selectedUnit) {
                $selectedBuildingId = $selectedUnit->building_id;
            }
        }

        if (! $selectedBuildingId && $buildings->count() === 1) {
            $selectedBuildingId = $buildings->first()->id;
        }

        return view('tenants.create', compact('units', 'buildings', 'selectedUnitId', 'selectedBuildingId'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('tenants.create'), 403);

        $supportsProfileImage = $this->tenantSupportsProfileImage();

        $rules = [
            'full_name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'building_id' => 'required|exists:buildings,id',
            'unit_number' => 'required|string',
            'move_in_date' => 'required|date',
            'move_out_date' => 'nullable|date|after:move_in_date',
            'active' => 'boolean',
        ];

        if ($supportsProfileImage) {
            $rules['profile_image'] = 'nullable|image|max:2048';
        }

        $validated = $request->validate($rules);

        // Find unit by building_id and unit_number with flexible matching
        $inputUnitNumber = trim($validated['unit_number']);
        $building = Building::withCount([
            'units',
            'units as occupied_units_count' => function ($query) {
                $query->where('status', 'OCCUPIED');
            },
        ])->findOrFail($validated['building_id']);

        if (auth()->user()->isManager() && (int) $building->manager_id !== (int) auth()->id()) {
            abort(403, 'You can only register tenants in your assigned building.');
        }

        $company = \App\Models\Company::query()->find($building->company_id);
        $validated['email'] = AutoEmailGenerator::generate(
            $validated['full_name'],
            'tenant',
            AutoEmailGenerator::domainForCompany($company)
        );

        $isActiveTenant = $request->boolean('active');

        // Get all units for this building to perform flexible matching
        $buildingUnits = Unit::where('building_id', $validated['building_id'])->get();

        $unit = $buildingUnits->first(function ($u) use ($inputUnitNumber) {
            // Case-insensitive comparison of trimmed values
            return strcasecmp(trim($u->unit_number), $inputUnitNumber) === 0;
        });

        if (! $unit) {
            // Check if building has any units at all
            if ($buildingUnits->isEmpty()) {
                return back()->withInput()->withErrors(['unit_number' => 'This building has 0 units in the system. You must create the unit (e.g., "101") in the "Units" section before assigning a tenant to it.']);
            }

            // Construct a helpful error message
            $availableExamples = $buildingUnits->take(3)->pluck('unit_number')->implode(', ');
            $errorMsg = "Unit '$inputUnitNumber' not found in selected building. Available units include: $availableExamples".($buildingUnits->count() > 3 ? ', etc.' : '.');

            return back()->withInput()->withErrors(['unit_number' => $errorMsg]);
        }

        if ($isActiveTenant && $building->units_count > 0 && $building->occupied_units_count >= $building->units_count) {
            return back()->withInput()->withErrors([
                'building_id' => 'This building is already at 100% occupancy. No additional active tenant can be added until a unit becomes available.',
            ]);
        }

        if ($isActiveTenant && $unit->status !== 'AVAILABLE') {
            return back()->withInput()->withErrors([
                'unit_number' => 'The selected unit is not available for a new active tenant.',
            ]);
        }

        $validated['unit_id'] = $unit->id;
        unset($validated['unit_number'], $validated['building_id']);

        if ($supportsProfileImage && $request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('tenants', 'public');
            $validated['profile_image'] = $path;
        }

        // Check for existing placeholder tenant (registered user without unit)
        $email = $validated['email'] ?? null;
        $tenantToUpdate = null;

        if ($email) {
            $tenantToUpdate = Tenant::where('email', $email)->whereNull('unit_id')->first();
        }

        if ($tenantToUpdate) {
            $oldValues = $tenantToUpdate->toArray();
            $tenantToUpdate->update($validated);
            $tenant = $tenantToUpdate;
            $message = 'Existing tenant profile updated with lease details.';
            app(AuditLogService::class)->log('tenant_updated', $tenant, $oldValues, $tenant->toArray());
        } else {
            // Link to user if exists
            if ($email) {
                $existingUser = User::where('email', $email)->first();
                if ($existingUser) {
                    $validated['user_id'] = $existingUser->id;
                }
            }
            $tenant = Tenant::create($validated);
            $message = 'Tenant registered successfully.';
            app(AuditLogService::class)->log('tenant_created', $tenant, null, $tenant->toArray());
        }

        // Update unit status to OCCUPIED if tenant is active
        if ($tenant->active) {
            $unit = Unit::find($validated['unit_id']);
            if ($unit) {
                $unit->update(['status' => 'OCCUPIED']);
            }
        }

        return redirect()->route('buildings.show', $request->building_id)->with('success', $message);
    }

    public function show(Tenant $tenant)
    {
        abort_unless(auth()->user()->hasPermission('tenants.view'), 403);

        $tenant->load(['unit.building', 'rents.payments', 'payments']);
        if (auth()->user()->isManager()) {
            $buildingManagerId = optional(optional($tenant->unit)->building)->manager_id;
            if ($buildingManagerId !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        $totalPaid = $tenant->rents->sum(function ($rent) {
            return (float) $rent->total_paid;
        });

        $outstandingBalance = $tenant->rents->sum(function ($rent) {
            return (float) $rent->balance;
        });

        $reminderMilestones = RentReminderService::milestones();

        return view('tenants.show', compact('tenant', 'totalPaid', 'outstandingBalance', 'reminderMilestones'));
    }

    public function updateReminderSettings(Request $request, Tenant $tenant)
    {
        $user = auth()->user();

        // Only admins and the tenant's assigned building manager can change reminder settings.
        if (! $user->isAdmin()) {
            $buildingManagerId = (int) optional(optional($tenant->unit)->building)->manager_id;
            abort_unless($user->isManager() && $buildingManagerId === (int) $user->id, 403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'rent_reminders_exempt' => 'nullable|boolean',
            'rent_reminders_start_milestone' => 'nullable|string|in:'.implode(',', array_column(RentReminderService::milestones(), 'key')),
        ]);

        $oldValues = $tenant->only(['rent_reminders_exempt', 'rent_reminders_start_milestone']);

        $exempt = $request->boolean('rent_reminders_exempt');
        $startMilestone = $validated['rent_reminders_start_milestone'] ?? null;

        // When re-enabling reminders, the start milestone is the floor of the sequence.
        // When exempting, keep the start milestone so it is ready on resume.
        $tenant->update([
            'rent_reminders_exempt' => $exempt,
            'rent_reminders_start_milestone' => $exempt ? $tenant->rent_reminders_start_milestone : ($startMilestone ?: null),
        ]);

        app(AuditLogService::class)->log(
            'tenant_rent_reminders_updated',
            $tenant,
            $oldValues,
            $tenant->only(['rent_reminders_exempt', 'rent_reminders_start_milestone'])
        );

        $message = $exempt
            ? 'Rent reminders have been paused for this tenant.'
            : 'Rent reminders have been resumed'.($startMilestone ? ' from "'.RentReminderService::milestoneByKey($startMilestone)['label'].'".' : ' with the full sequence.');

        return redirect()->route('tenants.show', $tenant)->with('success', $message);
    }

    public function edit(Tenant $tenant)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('tenants.edit'), 403);

        if ($user->isManager()) {
            $buildingManagerId = (int) optional(optional($tenant->unit)->building)->manager_id;
            abort_unless($buildingManagerId === (int) $user->id, 403, 'Unauthorized action.');
            abort_unless(
                ManagerEditRequest::hasActivePermissionFor($user->id, $tenant),
                403,
                ManagerEditRequest::denialMessageFor($user->id, $tenant)
            );
        } else {
            abort_unless($user->isAdmin(), 403, 'Managers cannot edit tenants directly. Request admin edit instead.');
        }

        $units = Unit::with('building')
            ->when($user->isManager(), function ($query) use ($user, $tenant) {
                $query->where(function ($unitQuery) use ($user, $tenant) {
                    $unitQuery
                        ->whereHas('building', function ($buildingQuery) use ($user) {
                            $buildingQuery->where('manager_id', $user->id);
                        })
                        ->orWhere('id', $tenant->unit_id);
                });
            })
            ->get();

        return view('tenants.edit', compact('tenant', 'units'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('tenants.edit'), 403);

        if ($user->isManager()) {
            $buildingManagerId = (int) optional(optional($tenant->unit)->building)->manager_id;
            abort_unless($buildingManagerId === (int) $user->id, 403, 'Unauthorized action.');
            abort_unless(
                ManagerEditRequest::hasActivePermissionFor($user->id, $tenant),
                403,
                ManagerEditRequest::denialMessageFor($user->id, $tenant)
            );
        } else {
            abort_unless($user->isAdmin(), 403, 'Managers cannot edit tenants directly. Request admin edit instead.');
        }

        $supportsProfileImage = $this->tenantSupportsProfileImage();

        $rules = [
            'full_name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'unit_id' => 'required|exists:units,id',
            'move_in_date' => 'required|date',
            'move_out_date' => 'nullable|date|after:move_in_date',
            'active' => 'boolean',
        ];

        if ($supportsProfileImage) {
            $rules['profile_image'] = 'nullable|image|max:2048';
        }

        $validated = $request->validate($rules);

        if ($user->isManager()) {
            $targetUnit = Unit::with('building')->findOrFail($validated['unit_id']);
            abort_unless((int) optional($targetUnit->building)->manager_id === (int) $user->id, 403, 'You can only move tenants within your assigned buildings.');
        }

        if ($supportsProfileImage && $request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($tenant->profile_image) {
                Storage::disk('public')->delete($tenant->profile_image);
            }
            $path = $request->file('profile_image')->store('tenants', 'public');
            $validated['profile_image'] = $path;
        }

        $oldUnitId = $tenant->unit_id;
        $oldValues = $tenant->toArray();
        $tenant->update($validated);
        app(AuditLogService::class)->log('tenant_updated', $tenant, $oldValues, $tenant->toArray());

        // Handle Unit status changes
        if ($oldUnitId != $tenant->unit_id) {
            // Make old unit AVAILABLE (if no other active tenants)
            // Ideally we check if anyone else is there, but usually 1 unit = 1 tenant/family
            Unit::where('id', $oldUnitId)->update(['status' => 'AVAILABLE']);

            // Make new unit OCCUPIED
            if ($tenant->active) {
                Unit::where('id', $tenant->unit_id)->update(['status' => 'OCCUPIED']);
            }
        } elseif (! $tenant->active) {
            // If tenant marked inactive, free up the unit
            Unit::where('id', $tenant->unit_id)->update(['status' => 'AVAILABLE']);
        }

        $this->deactivateLinkedUserIfTenantInactive($tenant);

        return redirect()->route('tenants.index')->with('success', 'Tenant updated successfully.');
    }

    protected function tenantSupportsProfileImage(): bool
    {
        return Schema::hasColumn('tenants', 'profile_image');
    }

    public function destroy(Tenant $tenant)
    {
        if (! auth()->user()->hasPermission('tenants.delete')) {
            return back()->with('error', 'Unauthorized action. Only admins can delete tenants.');
        }

        // Check for payments history
        if ($tenant->payments()->exists()) {
            return back()->with('error', 'Cannot delete tenant with payment history.');
        }

        $unitId = $tenant->unit_id;
        app(AuditLogService::class)->log('tenant_deleted', $tenant, $tenant->toArray(), null);
        $tenant->delete();

        // Set unit to available
        Unit::where('id', $unitId)->update(['status' => 'AVAILABLE']);

        return redirect()->route('tenants.index')->with('success', 'Tenant deleted successfully.');
    }

    public function evict(Request $request, Tenant $tenant)
    {
        $tenant->load(['company', 'unit.building.manager', 'rents.payments']);

        if (! auth()->user()->hasPermission('tenants.edit')) {
            abort(403, 'Unauthorized action. Only admins can evict tenants.');
        }

        if (! $tenant->active) {
            return back()->with('error', 'This tenant is already marked as former/inactive.');
        }

        $exportPath = $this->storeEvictionExport($tenant);

        DB::transaction(function () use ($tenant) {
            $today = now()->toDateString();

            $tenant->rents->where('status', 'ACTIVE')->each(function ($rent) use ($today) {
                $rent->update([
                    'status' => 'TERMINATED',
                    'end_date' => $today,
                ]);
            });

            $tenant->update([
                'active' => false,
                'move_out_date' => $tenant->move_out_date ?: $today,
            ]);

            if ($tenant->unit_id) {
                Unit::where('id', $tenant->unit_id)->update(['status' => 'AVAILABLE']);
            }

            $this->deactivateLinkedUserIfTenantInactive($tenant->fresh(['user']));
        });

        app(AuditLogService::class)->log('tenant_evicted', $tenant->fresh(), null, $tenant->fresh()->toArray());

        return redirect()
            ->route('tenants.show', $tenant)
            ->with('success', 'Tenant evicted successfully. The tenant was archived as former/inactive, the active lease was closed, and the unit is now available for a new tenant.')
            ->with('eviction_export_url', route('tenants.eviction-export', [
                'tenant' => $tenant,
                'token' => Crypt::encryptString($exportPath),
            ]))
            ->with('eviction_export_name', basename($exportPath));
    }

    public function downloadEvictionExport(Request $request, Tenant $tenant, ArchivePathService $archivePathService)
    {
        abort_unless(auth()->user()->hasPermission('tenants.view'), 403);

        $tenant->loadMissing(['company', 'unit.building.company']);

        if (auth()->user()->isManager()) {
            $buildingManagerId = (int) optional(optional($tenant->unit)->building)->manager_id;
            abort_unless($buildingManagerId === (int) auth()->id(), 403, 'Unauthorized action.');
        }

        $token = (string) $request->query('token', '');
        abort_if($token === '', 404);

        try {
            $path = Crypt::decryptString($token);
        } catch (\Throwable $exception) {
            abort(404);
        }

        $company = $tenant->company ?? optional(optional($tenant->unit)->building)->company;
        abort_unless($archivePathService->pathBelongsToCompany($company, $path), 403, 'Unauthorized action.');
        abort_unless($archivePathService->exists($path), 404);

        return $archivePathService->download($path, basename($path));
    }

    private function storeEvictionExport(Tenant $tenant): string
    {
        $building = optional($tenant->unit)->building;
        $company = $tenant->company ?? optional($building)->company;
        $manager = optional($building)->manager;
        $totalPaid = $tenant->rents->sum(function ($rent) {
            return (float) $rent->total_paid;
        });
        $outstandingBalance = $tenant->rents->sum(function ($rent) {
            return (float) $rent->balance;
        });

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['Eviction Export']);
        fputcsv($handle, ['Generated At', now()->format('Y-m-d H:i:s')]);
        fputcsv($handle, []);
        fputcsv($handle, ['Tenant Summary']);
        fputcsv($handle, ['Tenant ID', $tenant->id]);
        fputcsv($handle, ['Full Name', $tenant->full_name]);
        fputcsv($handle, ['Email', $tenant->email ?? '']);
        fputcsv($handle, ['Phone', $tenant->phone ?? '']);
        fputcsv($handle, ['Move In Date', optional($tenant->move_in_date)->format('Y-m-d')]);
        fputcsv($handle, ['Move Out Date', optional($tenant->move_out_date)->format('Y-m-d')]);
        fputcsv($handle, ['Active Status', $tenant->active ? 'Active' : 'Inactive']);
        fputcsv($handle, ['Building', optional($building)->name ?? '']);
        fputcsv($handle, ['Unit', optional($tenant->unit)->unit_number ?? '']);
        fputcsv($handle, ['Assigned Manager', optional($manager)->name ?? '']);
        fputcsv($handle, ['Manager Email', optional($manager)->email ?? '']);
        fputcsv($handle, ['Manager Phone', optional($manager)->phone ?? '']);
        fputcsv($handle, ['Total Paid', number_format($totalPaid, 2, '.', '')]);
        fputcsv($handle, ['Outstanding Balance', number_format($outstandingBalance, 2, '.', '')]);
        fputcsv($handle, []);

        fputcsv($handle, ['Lease History']);
        fputcsv($handle, ['Lease ID', 'Status', 'Start Date', 'End Date', 'Rent Amount', 'Rent Duration', 'Total Paid', 'Outstanding']);
        foreach ($tenant->rents->sortByDesc('start_date') as $rent) {
            fputcsv($handle, [
                $rent->id,
                $rent->status,
                optional($rent->start_date)->format('Y-m-d'),
                optional($rent->end_date)->format('Y-m-d'),
                number_format((float) $rent->annual_amount, 2, '.', ''),
                $rent->rent_duration_label,
                number_format((float) $rent->total_paid, 2, '.', ''),
                number_format((float) $rent->balance, 2, '.', ''),
            ]);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['Payment History']);
        fputcsv($handle, ['Payment ID', 'Lease ID', 'Date', 'Amount', 'Method', 'Status', 'Reference']);
        foreach ($tenant->payments->sortByDesc('payment_date') as $payment) {
            fputcsv($handle, [
                $payment->id,
                $payment->rent_id,
                optional($payment->payment_date)->format('Y-m-d'),
                number_format((float) $payment->amount, 2, '.', ''),
                $payment->payment_method,
                $payment->status,
                $payment->reference,
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $tenant->full_name);
        $fileName = 'tenant_eviction_'.$tenant->id.'_'.$safeName.'_'.now()->format('Ymd_His').'.csv';

        return app(ArchivePathService::class)->storeCsv($company, $fileName, $csvContent);
    }

    private function deactivateLinkedUserIfTenantInactive(Tenant $tenant): void
    {
        if ($tenant->active) {
            return;
        }

        $user = $tenant->relationLoaded('user') ? $tenant->user : $tenant->user()->first();

        if (! $user && filled($tenant->email)) {
            $user = User::where('email', $tenant->email)->first();
        }

        if ($user && $user->is_active) {
            $user->update(['is_active' => false]);
        }
    }
}
