<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\ManagerEditRequest;
use App\Models\Rent;
use App\Models\Unit;
use App\Support\AuditLogService;
use App\Support\UnitWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('buildings.view'), 403);

        $units = Unit::with('building')
            ->when(auth()->user()->isManager(), function ($query) {
                $query->whereHas('building', function ($buildingQuery) {
                    $buildingQuery->where('manager_id', auth()->id());
                });
            })
            ->paginate(15);

        return view('units.index', compact('units'));
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('buildings.create'), 403);

        $buildings = Building::query()
            ->when(auth()->user()->isManager(), function ($query) {
                $query->where('manager_id', auth()->id());
            })
            ->withCount(['units', 'availableUnits'])
            ->get();
        $selectedBuildingId = $request->query('building_id');
        $rentDurationOptions = Rent::rentDurationOptions();
        $rentDurationSupported = Rent::supportsRentDuration();
        $paymentMethodOptions = [
            'CASH' => 'Cash',
            'BANK_TRANSFER' => 'Bank Transfer',
            'CHECK' => 'Check',
            'ONLINE' => 'Online',
            'OTHER' => 'Other',
        ];
        $paymentStatusOptions = [
            'PENDING' => 'Pending',
            'COMPLETED' => 'Completed',
        ];
        $canCreateTenantWorkflow = auth()->user()->hasPermission('tenants.create');
        $canCreatePaymentWorkflow = auth()->user()->hasPermission('payments.create');

        return view('units.create', compact(
            'buildings',
            'selectedBuildingId',
            'rentDurationOptions',
            'rentDurationSupported',
            'paymentMethodOptions',
            'paymentStatusOptions',
            'canCreateTenantWorkflow',
            'canCreatePaymentWorkflow',
        ));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('buildings.create'), 403);

        $workflowEnabled = $request->boolean('unit_workflow_enabled');
        $createFinancialEntry = $workflowEnabled && $request->boolean('create_financial_entry');

        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'unit_number' => 'required|string|max:50',
            'floor' => 'nullable|string|max:20',
            'bedrooms' => 'required|integer|min:0',
            'bathrooms' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['AVAILABLE', 'OCCUPIED', 'MAINTENANCE'])],
            'unit_workflow_enabled' => 'nullable|boolean',
            'tenant_full_name' => [Rule::requiredIf($workflowEnabled), 'nullable', 'string', 'max:150'],
            'tenant_phone' => 'nullable|string|max:50',
            'tenant_move_in_date' => [Rule::requiredIf($workflowEnabled), 'nullable', 'date'],
            'lease_annual_amount' => [Rule::requiredIf($workflowEnabled), 'nullable', 'numeric', 'min:0'],
            'lease_rent_duration' => [Rule::requiredIf($workflowEnabled), 'nullable', Rule::in(array_keys(Rent::rentDurationOptions()))],
            'lease_start_date' => [Rule::requiredIf($workflowEnabled), 'nullable', 'date'],
            'lease_end_date' => 'nullable|date|after:lease_start_date',
            'create_financial_entry' => 'nullable|boolean',
            'financial_amount' => [Rule::requiredIf($createFinancialEntry), 'nullable', 'numeric', 'min:0.01'],
            'financial_payment_date' => [Rule::requiredIf($createFinancialEntry), 'nullable', 'date'],
            'financial_payment_method' => 'nullable|string|max:50',
            'financial_status' => ['nullable', Rule::in(['PENDING', 'COMPLETED'])],
        ]);

        // Check for duplicate unit in same building
        $exists = Unit::where('building_id', $request->building_id)
            ->where('unit_number', $request->unit_number)
            ->where('floor', $request->floor)
            ->exists();

        if ($exists) {
            return back()->withErrors(['unit_number' => 'This unit already exists in the building.'])->withInput();
        }

        // Check if building has reached its total unit capacity
        $building = Building::find($request->building_id);
        if (auth()->user()->isManager()) {
            abort_unless((int) optional($building)->manager_id === (int) auth()->id(), 403, 'You can only create units in your assigned buildings.');
        }

        if ($workflowEnabled && ! auth()->user()->hasPermission('tenants.create')) {
            abort(403, 'You do not have permission to create tenants from the Units form.');
        }

        if ($createFinancialEntry && ! auth()->user()->hasPermission('payments.create')) {
            abort(403, 'You do not have permission to create financial records from the Units form.');
        }

        if ($workflowEnabled && ! Rent::supportsRentDuration()) {
            return back()
                ->withInput()
                ->withErrors(['lease_rent_duration' => 'Rent duration is not available on this deployment yet. Run the rent duration migration before using the automated unit workflow.']);
        }

        if ($building && $building->total_units > 0) {
            $currentUnitCount = Unit::where('building_id', $building->id)->count();
            if ($currentUnitCount >= $building->total_units) {
                $availableUnitCount = Unit::where('building_id', $building->id)
                    ->where('status', 'AVAILABLE')
                    ->count();

                $message = $availableUnitCount > 0
                    ? "This building already has {$building->total_units} of {$building->total_units} units created, but {$availableUnitCount} vacant unit".($availableUnitCount === 1 ? ' is' : 's are')." available for reuse. Keep the existing unit number and details, then assign the next tenant to that vacant unit instead of creating a new one."
                    : "This building has reached its capacity of {$building->total_units} units.";

                return back()->withErrors(['building_id' => $message])->withInput();
            }
        }

        $workflowResult = app(UnitWorkflowService::class)->createFromUnitForm(
            $building,
            $validated,
            auth()->user()
        );

        $unit = $workflowResult['unit'];
        app(AuditLogService::class)->log('unit_created', $unit, null, $unit->toArray());

        if (! empty($workflowResult['tenant'])) {
            app(AuditLogService::class)->log('tenant_created', $workflowResult['tenant'], null, $workflowResult['tenant']->toArray());
        }

        if (! empty($workflowResult['rent'])) {
            app(AuditLogService::class)->log('rent_created', $workflowResult['rent'], null, $workflowResult['rent']->toArray());
        }

        if (! empty($workflowResult['payment'])) {
            app(AuditLogService::class)->log('payment_created', $workflowResult['payment'], null, $workflowResult['payment']->toArray());
        }

        $redirectTab = ! empty($workflowResult['payment']) ? 'payments' : (! empty($workflowResult['rent']) ? 'rents' : 'units');
        $successMessage = 'Unit created successfully.';

        if (! empty($workflowResult['rent']) && ! empty($workflowResult['payment'])) {
            $successMessage = 'Unit created successfully. Tenant, lease agreement, and financial records were added automatically.';
        } elseif (! empty($workflowResult['rent'])) {
            $successMessage = 'Unit created successfully. Tenant and lease agreement records were added automatically.';
        }

        return redirect()
            ->route('buildings.show', ['building' => $validated['building_id'], 'tab' => $redirectTab])
            ->with('success', $successMessage);
    }

    public function show(Unit $unit)
    {
        abort_unless(auth()->user()->hasPermission('buildings.view'), 403);

        $unit->load(['building', 'rents.tenant']);

        if (auth()->user()->isManager()) {
            abort_unless((int) optional($unit->building)->manager_id === (int) auth()->id(), 403, 'Unauthorized action.');
        }

        return view('units.show', compact('unit'));
    }

    public function edit(Unit $unit)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('buildings.edit'), 403);

        $unit->load(['building', 'currentTenant', 'rents']);

        if ($user->isManager()) {
            abort_unless((int) optional($unit->building)->manager_id === (int) $user->id, 403, 'Unauthorized action.');
            abort_unless(
                ManagerEditRequest::hasActivePermissionFor($user->id, $unit),
                403,
                ManagerEditRequest::denialMessageFor($user->id, $unit)
            );
        }

        $buildings = Building::query()
            ->when($user->isManager(), function ($query) use ($user, $unit) {
                $query->where(function ($buildingQuery) use ($user, $unit) {
                    $buildingQuery
                        ->where('manager_id', $user->id)
                        ->orWhere('id', $unit->building_id);
                });
            })
            ->get();

        $rentDurationOptions = Rent::rentDurationOptions();
        $rentDurationSupported = Rent::supportsRentDuration();
        $paymentMethodOptions = [
            'CASH' => 'Cash',
            'BANK_TRANSFER' => 'Bank Transfer',
            'CHECK' => 'Check',
            'ONLINE' => 'Online',
            'OTHER' => 'Other',
        ];
        $paymentStatusOptions = [
            'PENDING' => 'Pending',
            'COMPLETED' => 'Completed',
        ];
        $canCreateTenantWorkflow = $user->hasPermission('tenants.create');
        $canCreatePaymentWorkflow = $user->hasPermission('payments.create');
        $hasActiveLease = $unit->rents->where('status', 'ACTIVE')->isNotEmpty();

        return view('units.edit', compact(
            'unit',
            'buildings',
            'rentDurationOptions',
            'rentDurationSupported',
            'paymentMethodOptions',
            'paymentStatusOptions',
            'canCreateTenantWorkflow',
            'canCreatePaymentWorkflow',
            'hasActiveLease',
        ));
    }

    public function update(Request $request, Unit $unit)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('buildings.edit'), 403);

        $unit->loadMissing(['building', 'rents']);

        if ($user->isManager()) {
            abort_unless((int) optional($unit->building)->manager_id === (int) $user->id, 403, 'Unauthorized action.');
            abort_unless(
                ManagerEditRequest::hasActivePermissionFor($user->id, $unit),
                403,
                ManagerEditRequest::denialMessageFor($user->id, $unit)
            );
        }

        $workflowEnabled = $request->boolean('unit_workflow_enabled');
        $createFinancialEntry = $workflowEnabled && $request->boolean('create_financial_entry');

        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'unit_number' => 'required|string|max:50',
            'floor' => 'nullable|string|max:20',
            'bedrooms' => 'required|integer|min:0',
            'bathrooms' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['AVAILABLE', 'OCCUPIED', 'MAINTENANCE'])],
            'unit_workflow_enabled' => 'nullable|boolean',
            'tenant_full_name' => [Rule::requiredIf($workflowEnabled), 'nullable', 'string', 'max:150'],
            'tenant_phone' => 'nullable|string|max:50',
            'tenant_move_in_date' => [Rule::requiredIf($workflowEnabled), 'nullable', 'date'],
            'lease_annual_amount' => [Rule::requiredIf($workflowEnabled), 'nullable', 'numeric', 'min:0'],
            'lease_rent_duration' => [Rule::requiredIf($workflowEnabled), 'nullable', Rule::in(array_keys(Rent::rentDurationOptions()))],
            'lease_start_date' => [Rule::requiredIf($workflowEnabled), 'nullable', 'date'],
            'lease_end_date' => 'nullable|date|after:lease_start_date',
            'create_financial_entry' => 'nullable|boolean',
            'financial_amount' => [Rule::requiredIf($createFinancialEntry), 'nullable', 'numeric', 'min:0.01'],
            'financial_payment_date' => [Rule::requiredIf($createFinancialEntry), 'nullable', 'date'],
            'financial_payment_method' => 'nullable|string|max:50',
            'financial_status' => ['nullable', Rule::in(['PENDING', 'COMPLETED'])],
        ]);

        if ($user->isManager()) {
            $targetBuilding = Building::findOrFail($validated['building_id']);
            abort_unless((int) $targetBuilding->manager_id === (int) $user->id, 403, 'You can only move units within your assigned buildings.');
        }

        if ($workflowEnabled && ! $user->hasPermission('tenants.create')) {
            abort(403, 'You do not have permission to create tenants from the Edit Unit form.');
        }

        if ($createFinancialEntry && ! $user->hasPermission('payments.create')) {
            abort(403, 'You do not have permission to create financial records from the Edit Unit form.');
        }

        if ($workflowEnabled && ! Rent::supportsRentDuration()) {
            return back()
                ->withInput()
                ->withErrors(['lease_rent_duration' => 'Rent duration is not available on this deployment yet. Run the rent duration migration before using the automated unit workflow.']);
        }

        if ($workflowEnabled && $unit->rents->where('status', 'ACTIVE')->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['unit_workflow_enabled' => 'This unit already has an active lease. Finish or terminate the current lease before using Edit Unit to create a new tenant workflow.']);
        }

        // Check uniqueness excluding current unit
        $exists = Unit::where('building_id', $request->building_id)
            ->where('unit_number', $request->unit_number)
            ->where('floor', $request->floor)
            ->where('id', '!=', $unit->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['unit_number' => 'This unit already exists in the building.'])->withInput();
        }

        $oldValues = $unit->toArray();
        $unit->update($validated);
        app(AuditLogService::class)->log('unit_updated', $unit, $oldValues, $unit->toArray());

        $workflowResult = [
            'tenant' => null,
            'rent' => null,
            'payment' => null,
        ];

        if ($workflowEnabled) {
            try {
                $workflowResult = app(UnitWorkflowService::class)->attachToExistingUnit(
                    $unit->fresh(['building', 'rents']),
                    $validated,
                    $user
                );
            } catch (\RuntimeException $exception) {
                return back()->withInput()->withErrors(['unit_workflow_enabled' => $exception->getMessage()]);
            }

            if (! empty($workflowResult['tenant'])) {
                app(AuditLogService::class)->log('tenant_created', $workflowResult['tenant'], null, $workflowResult['tenant']->toArray());
            }

            if (! empty($workflowResult['rent'])) {
                app(AuditLogService::class)->log('rent_created', $workflowResult['rent'], null, $workflowResult['rent']->toArray());
            }

            if (! empty($workflowResult['payment'])) {
                app(AuditLogService::class)->log('payment_created', $workflowResult['payment'], null, $workflowResult['payment']->toArray());
            }
        }

        $redirectTab = ! empty($workflowResult['payment']) ? 'payments' : (! empty($workflowResult['rent']) ? 'rents' : 'units');
        $successMessage = 'Unit updated successfully.';

        if (! empty($workflowResult['rent']) && ! empty($workflowResult['payment'])) {
            $successMessage = 'Unit updated successfully. Tenant, lease agreement, and financial records were added automatically.';
        } elseif (! empty($workflowResult['rent'])) {
            $successMessage = 'Unit updated successfully. Tenant and lease agreement records were added automatically.';
        }

        return redirect()
            ->route('buildings.show', ['building' => $unit->building_id, 'tab' => $redirectTab])
            ->with('success', $successMessage);
    }

    public function destroy(Unit $unit)
    {
        if (! auth()->user()->hasPermission('buildings.delete')) {
            return back()->with('error', 'Unauthorized action. Only admins can delete units.');
        }

        if ($unit->status === 'OCCUPIED') {
            return back()->with('error', 'Cannot delete an occupied unit.');
        }

        if ($unit->rents()->exists()) {
            return back()->with('error', 'Cannot delete unit with rental history.');
        }

        app(AuditLogService::class)->log('unit_deleted', $unit, $unit->toArray(), null);
        $unit->delete();

        return redirect()->route('units.index')->with('success', 'Unit deleted successfully.');
    }
}
