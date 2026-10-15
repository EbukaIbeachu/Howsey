<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\ArchivePathService;
use App\Support\AuditLogService;
use App\Support\RoleRequestNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class MaintenanceRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $activeStatuses = ['PENDING', 'IN_PROGRESS'];
        abort_unless($user->hasPermission('maintenance.view'), 403);

        if ($user->isAdmin() || $user->isManager()) {
            $requests = MaintenanceRequest::with(['unit.building', 'tenant', 'creator'])
                ->whereIn('status', $activeStatuses)
                ->latest()
                ->paginate(10);
        } else {
            // Assume tenant user
            $tenant = Tenant::where('user_id', $user->id)->first();
            if ($tenant) {
                $requests = MaintenanceRequest::with(['unit.building', 'tenant'])
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('status', $activeStatuses)
                    ->latest()
                    ->paginate(10);
            } else {
                $requests = collect(); // Empty collection if no tenant record found
            }
        }

        return view('maintenance.index', compact('requests'));
    }

    public function create()
    {
        $user = Auth::user();
        abort_unless($user->hasPermission('maintenance.create'), 403);
        $units = [];
        $tenants = [];

        if ($user->isAdmin() || $user->isManager()) {
            $units = Unit::with('building')->get(); // Ideally filter by occupied
            $tenants = Tenant::active()->get();
        }

        return view('maintenance.create', compact('units', 'tenants'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->hasPermission('maintenance.create'), 403);
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:PLUMBING,ELECTRICAL,HVAC,STRUCTURAL,APPLIANCE,OTHER',
            'priority' => 'required|in:LOW,MEDIUM,HIGH,EMERGENCY',
        ];

        if ($user->isAdmin() || $user->isManager()) {
            $rules['unit_id'] = 'required|exists:units,id';
            $rules['tenant_id'] = 'required|exists:tenants,id';
        }

        $validated = $request->validate($rules);

        if (! ($user->isAdmin() || $user->isManager())) {
            $tenant = Tenant::where('user_id', $user->id)->firstOrFail();
            $validated['tenant_id'] = $tenant->id;
            $validated['unit_id'] = $tenant->unit_id; // Assuming tenant is assigned to a unit
            if (! $validated['unit_id']) {
                // Fallback if tenant not currently in a unit (edge case)
                return back()->with('error', 'You are not currently assigned to a unit.');
            }
        }

        $validated['status'] = 'PENDING';

        $maintenanceRequest = MaintenanceRequest::create($validated);
        app(AuditLogService::class)->log('maintenance_created', $maintenanceRequest, null, $maintenanceRequest->toArray());

        $maintenanceRequest->loadMissing(['unit.building.manager']);
        $manager = optional(optional($maintenanceRequest->unit)->building)->manager;

        RoleRequestNotifier::notifyAdminsAndManager($manager, [
            'title' => 'Maintenance request submitted',
            'message' => $user->name.' submitted a maintenance request that needs attention.',
            'url' => route('maintenance.index'),
            'request_type' => 'maintenance',
            'created_by' => $user->name,
            'subject_id' => $maintenanceRequest->id,
        ], $user->id);

        return redirect()->route('maintenance.index')->with('success', 'Maintenance request submitted successfully.');
    }

    public function show(MaintenanceRequest $maintenance)
    {
        abort_unless(Auth::user()->hasPermission('maintenance.view'), 403);
        $maintenance->load(['unit.building', 'tenant']);

        return view('maintenance.show', compact('maintenance'));
    }

    public function edit(MaintenanceRequest $maintenance)
    {
        abort_unless(Auth::user()->hasPermission('maintenance.edit'), 403);

        $units = Unit::with('building')->get();
        $tenants = Tenant::active()->get();

        return view('maintenance.edit', compact('maintenance', 'units', 'tenants'));
    }

    public function update(Request $request, MaintenanceRequest $maintenance)
    {
        abort_unless(Auth::user()->hasPermission('maintenance.edit'), 403);

        $validated = $request->validate([
            'status' => 'required|in:PENDING,IN_PROGRESS,RESOLVED,CANCELLED',
            'priority' => 'required|in:LOW,MEDIUM,HIGH,EMERGENCY',
            'type' => 'required|in:PLUMBING,ELECTRICAL,HVAC,STRUCTURAL,APPLIANCE,OTHER',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        if ($validated['status'] === 'RESOLVED' && $maintenance->status !== 'RESOLVED') {
            $validated['resolved_at'] = now();
        } elseif ($validated['status'] !== 'RESOLVED') {
            $validated['resolved_at'] = null;
        }

        $exportPath = null;
        $terminalStatusSelected = $this->isTerminalMaintenanceStatus($validated['status']);
        $statusChanged = $maintenance->status !== $validated['status'];

        $oldValues = $maintenance->toArray();
        $maintenance->update($validated);
        app(AuditLogService::class)->log('maintenance_updated', $maintenance, $oldValues, $maintenance->toArray());

        if ($terminalStatusSelected && $statusChanged) {
            $maintenance->loadMissing(['unit.building.manager', 'tenant', 'creator']);
            $exportPath = $this->storeMaintenanceExport($maintenance->fresh(['unit.building.manager', 'tenant', 'creator']));
        }

        $redirect = redirect()->route('maintenance.index');

        if ($exportPath) {
            return $redirect
                ->with('success', 'Maintenance request archived successfully. It was exported to the automatic system folder and removed from the active maintenance page.')
                ->with('maintenance_export_url', route('maintenance.archive-download', [
                    'maintenance' => $maintenance,
                    'token' => Crypt::encryptString($exportPath),
                ]))
                ->with('maintenance_export_name', basename($exportPath));
        }

        return $redirect->with('success', 'Maintenance request updated successfully.');
    }

    public function destroy(MaintenanceRequest $maintenance)
    {
        abort_unless(Auth::user()->hasPermission('maintenance.delete'), 403);

        app(AuditLogService::class)->log('maintenance_deleted', $maintenance, $maintenance->toArray(), null);
        $maintenance->delete();

        return redirect()->route('maintenance.index')->with('success', 'Maintenance request deleted successfully.');
    }

    public function downloadArchive(Request $request, MaintenanceRequest $maintenance, ArchivePathService $archivePathService)
    {
        $user = Auth::user();
        abort_unless($user->hasPermission('maintenance.view'), 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403, 'Unauthorized action.');

        $token = (string) $request->query('token', '');
        abort_if($token === '', 404);

        try {
            $path = Crypt::decryptString($token);
        } catch (\Throwable $exception) {
            abort(404);
        }

        $maintenance->loadMissing(['company', 'unit.building.company']);
        $company = $maintenance->company ?? optional(optional($maintenance->unit)->building)->company;

        abort_unless($archivePathService->pathBelongsToCompany($company, $path), 403, 'Unauthorized action.');
        abort_unless($archivePathService->exists($path), 404);

        return $archivePathService->download($path, basename($path));
    }

    private function isTerminalMaintenanceStatus(string $status): bool
    {
        return in_array($status, ['RESOLVED', 'CANCELLED'], true);
    }

    private function storeMaintenanceExport(MaintenanceRequest $maintenance): string
    {
        $maintenance->loadMissing(['company', 'unit.building.manager', 'tenant', 'creator']);

        $company = $maintenance->company ?? optional(optional($maintenance->unit)->building)->company;
        $building = optional($maintenance->unit)->building;
        $manager = optional($building)->manager;
        $tenant = $maintenance->tenant;
        $creator = $maintenance->creator;

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['Maintenance Request Archive']);
        fputcsv($handle, ['Generated At', now()->format('Y-m-d H:i:s')]);
        fputcsv($handle, []);
        fputcsv($handle, ['Request Summary']);
        fputcsv($handle, ['Request ID', $maintenance->id]);
        fputcsv($handle, ['Title', $maintenance->title]);
        fputcsv($handle, ['Description', $maintenance->description]);
        fputcsv($handle, ['Type', $maintenance->type]);
        fputcsv($handle, ['Priority', $maintenance->priority]);
        fputcsv($handle, ['Status', $maintenance->status]);
        fputcsv($handle, ['Created At', optional($maintenance->created_at)->format('Y-m-d H:i:s')]);
        fputcsv($handle, ['Resolved At', optional($maintenance->resolved_at)->format('Y-m-d H:i:s')]);
        fputcsv($handle, []);
        fputcsv($handle, ['Tenant & Location']);
        fputcsv($handle, ['Tenant Name', optional($tenant)->full_name ?? '']);
        fputcsv($handle, ['Tenant Email', optional($tenant)->email ?? '']);
        fputcsv($handle, ['Tenant Phone', optional($tenant)->phone ?? '']);
        fputcsv($handle, ['Building', optional($building)->name ?? '']);
        fputcsv($handle, ['Unit', optional($maintenance->unit)->unit_number ?? '']);
        fputcsv($handle, []);
        fputcsv($handle, ['Assignment']);
        fputcsv($handle, ['Submitted By', optional($creator)->name ?? 'System']);
        fputcsv($handle, ['Submitted By Email', optional($creator)->email ?? '']);
        fputcsv($handle, ['Assigned Manager', optional($manager)->name ?? '']);
        fputcsv($handle, ['Manager Email', optional($manager)->email ?? '']);

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        $safeTitle = preg_replace('/[^A-Za-z0-9_-]+/', '_', $maintenance->title);
        $fileName = 'maintenance_'.$maintenance->id.'_'.strtolower($maintenance->status).'_'.$safeTitle.'_'.now()->format('Ymd_His').'.csv';

        return app(ArchivePathService::class)->storeCsv($company, $fileName, $csvContent);
    }
}
