<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\ManagerEditRequest;
use App\Models\Rent;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\AccountIdentityService;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $status = $request->query('status', 'ACTIVE'); // Default to ACTIVE

        $query = Rent::with(['tenant', 'unit.building', 'creator']);

        if ($user->isTenant()) {
            $query->whereHas('tenant', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->isManager()) {
            $query->whereHas('unit.building', function ($q) use ($user) {
                $q->where('manager_id', $user->id);
            });
        }

        if ($status && $status !== 'ALL') {
            $query->where('status', $status);
        }

        $rents = $query->paginate(15);

        return view('rents.index', [
            'rents' => $rents,
            'status' => $status,
            'pageTitle' => 'Rental Agreements',
            'showExpiredLeasesShortcut' => true,
        ]);
    }

    public function expiredLeases(Request $request)
    {
        $user = auth()->user();

        $query = Rent::with(['tenant', 'unit.building', 'creator'])
            ->where('status', 'EXPIRED')
            ->whereHas('tenant', function ($tenantQuery) {
                $tenantQuery->where('active', false);
            });

        if ($user->isTenant()) {
            $query->whereHas('tenant', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->isManager()) {
            $query->whereHas('unit.building', function ($q) use ($user) {
                $q->where('manager_id', $user->id);
            });
        }

        $rents = $query
            ->orderByDesc('end_date')
            ->orderByDesc('start_date')
            ->paginate(15);

        return view('rents.index', [
            'rents' => $rents,
            'status' => 'EXPIRED_ARCHIVED',
            'pageTitle' => 'Expired Leases',
            'showExpiredLeasesShortcut' => false,
        ]);
    }

    public function create(Request $request)
    {
        $tenants = Tenant::where('active', true)->with('unit')->get();
        $buildings = Building::all(); // Fetch all buildings for the filter
        $units = Unit::where('status', '!=', 'MAINTENANCE')->with('building')->get(); // Eager load building
        $selectedTenantId = $request->query('tenant_id');
        $selectedUnitId = $request->query('unit_id');
        $rentDurationOptions = Rent::rentDurationOptions();
        $rentDurationSupported = Rent::supportsRentDuration();

        return view('rents.create', compact('tenants', 'units', 'buildings', 'selectedTenantId', 'selectedUnitId', 'rentDurationOptions', 'rentDurationSupported'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'unit_id' => 'required|exists:units,id',
            'annual_amount' => 'required|numeric|min:0',
            'rent_duration' => 'nullable|in:daily,weekly,monthly,annually',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'nullable|in:ACTIVE,TERMINATED,EXPIRED',
        ]);

        $validated['status'] = $validated['status'] ?? 'ACTIVE';
        $validated['rent_duration'] = $validated['rent_duration'] ?? Rent::DURATION_ANNUALLY;
        $validated['due_day'] = $this->deriveDueDay($validated['start_date'], $validated['rent_duration']);

        if (! Rent::supportsRentDuration()) {
            return back()
                ->withInput()
                ->with('error', 'Rent duration is not yet available on this deployment. Please run the rent duration migration before creating or editing agreements.');
        }

        // Check for existing active lease for this tenant
        $existingRent = Rent::where('tenant_id', $validated['tenant_id'])
            ->where('status', 'ACTIVE')
            ->first();

        if ($existingRent) {
            // Mark the old lease as EXPIRED
            $existingRent->update(['status' => 'EXPIRED']);

            // If the tenant is moving to a new unit, free up the old unit
            if ($existingRent->unit_id != $validated['unit_id']) {
                $existingRent->unit->update(['status' => 'AVAILABLE']);
            }
        }

        $rent = Rent::create($validated);

        // Update Unit status and Tenant's current unit
        if ($rent->status === 'ACTIVE') {
            $unit = Unit::find($validated['unit_id']);
            if ($unit) {
                $unit->update(['status' => 'OCCUPIED']);
            }

            $tenant = Tenant::find($validated['tenant_id']);
            if ($tenant) {
                $tenant->update(['unit_id' => $validated['unit_id']]);
            }
        }

        return redirect()->route('buildings.show', Unit::find($validated['unit_id'])->building_id)->with('success', 'Rental agreement created successfully.');
    }

    public function show(Rent $rent)
    {
        $rent->load(['tenant', 'unit.building', 'payments']);

        $user = auth()->user();
        if ($user->isAdmin()) {
        } elseif ($user->isManager()) {
            if (! $rent->unit || ! $rent->unit->building || $rent->unit->building->manager_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
        } else {
            if (! $rent->tenant || $rent->tenant->user_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
        }

        return view('rents.show', compact('rent'));
    }

    public function edit(Rent $rent)
    {
        $user = auth()->user();

        if ($user->isManager()) {
            abort_unless((int) optional(optional($rent->unit)->building)->manager_id === (int) $user->id, 403, 'Unauthorized action.');
            abort_unless(
                ManagerEditRequest::hasActivePermissionFor($user->id, $rent),
                403,
                ManagerEditRequest::denialMessageFor($user->id, $rent)
            );
        } else {
            abort_unless($user->isAdmin(), 403, 'Managers cannot edit rental agreements directly. Request admin edit instead.');
        }

        $tenants = Tenant::query()
            ->when($user->isManager(), function ($query) use ($user, $rent) {
                $query->where(function ($tenantQuery) use ($user, $rent) {
                    $tenantQuery
                        ->whereHas('unit.building', function ($buildingQuery) use ($user) {
                            $buildingQuery->where('manager_id', $user->id);
                        })
                        ->orWhere('id', $rent->tenant_id);
                });
            })
            ->get();
        $buildings = $user->isManager()
            ? Building::where('manager_id', $user->id)->get()
            : Building::all();
        $units = Unit::with('building')
            ->when($user->isManager(), function ($query) use ($user, $rent) {
                $query->where(function ($unitQuery) use ($user, $rent) {
                    $unitQuery
                        ->whereHas('building', function ($buildingQuery) use ($user) {
                            $buildingQuery->where('manager_id', $user->id);
                        })
                        ->orWhere('id', $rent->unit_id);
                });
            })
            ->get();
        $rentDurationOptions = Rent::rentDurationOptions();
        $rentDurationSupported = Rent::supportsRentDuration();

        return view('rents.edit', compact('rent', 'tenants', 'units', 'buildings', 'rentDurationOptions', 'rentDurationSupported'));
    }

    public function update(Request $request, Rent $rent)
    {
        $user = auth()->user();

        if ($user->isManager()) {
            abort_unless((int) optional(optional($rent->unit)->building)->manager_id === (int) $user->id, 403, 'Unauthorized action.');
            abort_unless(
                ManagerEditRequest::hasActivePermissionFor($user->id, $rent),
                403,
                ManagerEditRequest::denialMessageFor($user->id, $rent)
            );
        } else {
            abort_unless($user->isAdmin(), 403, 'Managers cannot edit rental agreements directly. Request admin edit instead.');
        }

        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'unit_id' => 'required|exists:units,id',
            'annual_amount' => 'required|numeric|min:0',
            'rent_duration' => 'nullable|in:daily,weekly,monthly,annually',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:ACTIVE,TERMINATED,EXPIRED',
        ]);

        $validated['rent_duration'] = $validated['rent_duration'] ?? $rent->rent_duration_value;
        $validated['due_day'] = $this->deriveDueDay($validated['start_date'], $validated['rent_duration']);

        if ($user->isManager()) {
            $targetUnit = Unit::with('building')->findOrFail($validated['unit_id']);
            $targetTenant = Tenant::with('unit.building')->findOrFail($validated['tenant_id']);

            abort_unless((int) optional($targetUnit->building)->manager_id === (int) $user->id, 403, 'You can only assign units from your managed buildings.');
            abort_unless((int) optional(optional($targetTenant->unit)->building)->manager_id === (int) $user->id, 403, 'You can only assign tenants from your managed buildings.');
        }

        if (! Rent::supportsRentDuration()) {
            return back()
                ->withInput()
                ->with('error', 'Rent duration is not yet available on this deployment. Please run the rent duration migration before creating or editing agreements.');
        }

        $originalStatus = $rent->status;
        $originalUnitId = $rent->unit_id;
        $originalTenantId = $rent->tenant_id;

        $rent->update($validated);

        // 1. Handle Unit Changes
        if ($originalUnitId != $rent->unit_id) {
            // Old unit becomes available if it was occupied
            $oldUnit = Unit::find($originalUnitId);
            if ($oldUnit && $oldUnit->status === 'OCCUPIED') {
                // Only set to AVAILABLE if we assume this rent was the one occupying it.
                // Ideally we should check if there are other active rents, but simpler logic:
                $oldUnit->update(['status' => 'AVAILABLE']);
            }
        }

        // 2. Handle Tenant Changes
        if ($originalTenantId != $rent->tenant_id) {
            $oldTenant = Tenant::find($originalTenantId);
            if ($oldTenant) {
                $oldTenant->update(['unit_id' => null]);
            }
        }

        // 3. Apply Status Logic to Current (New) Unit/Tenant
        if ($rent->status === 'ACTIVE') {
            $rent->unit->update(['status' => 'OCCUPIED']);
            $rent->tenant->update(['unit_id' => $rent->unit_id]);
        } elseif (in_array($rent->status, ['TERMINATED', 'EXPIRED'])) {
            // If status is not active, free the current unit/tenant
            // But only if we didn't just switch FROM an active state on a different unit (already handled above somewhat)
            // Actually, simply:
            $rent->unit->update(['status' => 'AVAILABLE']);
            $rent->tenant->update(['unit_id' => null]);
        }

        return redirect()->route('rents.index')->with('success', 'Rental agreement updated successfully.');
    }

    public function destroy(Rent $rent)
    {
        if (! auth()->user()->isAdmin()) {
            return back()->with('error', 'Unauthorized action. Only admins can delete rental agreements.');
        }

        if ($rent->payments()->exists()) {
            return back()->with('error', 'Cannot delete rental agreement with recorded payments.');
        }

        $rent->delete();

        return redirect()->route('rents.index')->with('success', 'Rental agreement deleted successfully.');
    }

    public function agreement(Request $request, Rent $rent)
    {
        $rent->load(['tenant', 'unit.building', 'company.settings']);

        $user = auth()->user();
        $this->authorizeAgreementAccess($user, $rent);

        $signedAgreementSupported = Schema::hasColumn('rents', 'signed_agreement_path');
        $agreementWatermarkText = $this->agreementWatermarkText($user);
        $canRequestAgreementDownload = $this->canManagerRequestAgreementAction($user, $rent, ManagerEditRequest::REQUEST_KIND_AGREEMENT_DOWNLOAD);
        $canRequestAgreementDelete = $this->canManagerRequestAgreementAction($user, $rent, ManagerEditRequest::REQUEST_KIND_AGREEMENT_DELETE);
        $canDownloadUploadedAgreement = $this->canDownloadUploadedAgreement($user, $rent);
        $canDeleteUploadedAgreement = $this->canDeleteUploadedAgreement($user, $rent);

        if ($request->boolean('download')) {
            if (! app()->bound('dompdf.wrapper')) {
                app()->register(ServiceProvider::class);
            }

            $filename = 'Tenancy_Agreement_'.str_replace(' ', '_', $rent->tenant->full_name).'.pdf';

            return PdfFacade::loadView('rents.agreement-pdf', compact('rent', 'agreementWatermarkText'))
                ->setPaper('a4')
                ->download($filename);
        }

        $backUrl = $this->resolveAgreementBackUrl($request, $user, $rent);

        return view('rents.agreement', compact(
            'rent',
            'backUrl',
            'signedAgreementSupported',
            'agreementWatermarkText',
            'canRequestAgreementDownload',
            'canRequestAgreementDelete',
            'canDownloadUploadedAgreement',
            'canDeleteUploadedAgreement',
        ));
    }

    public function uploadAgreement(Request $request, Rent $rent)
    {
        $user = auth()->user();
        $this->authorizeAgreementAccess($user, $rent);

        $request->validate([
            'signed_agreement' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        if (! Schema::hasColumn('rents', 'signed_agreement_path')) {
            return back()->with('error', 'Signed agreement uploads are not available on this deployment yet. Run the signed agreement migration first.');
        }

        if ($request->hasFile('signed_agreement')) {
            // Delete old file if exists
            if ($rent->signed_agreement_path) {
                foreach (['private', 'public'] as $disk) {
                    if (Storage::disk($disk)->exists($rent->signed_agreement_path)) {
                        Storage::disk($disk)->delete($rent->signed_agreement_path);
                    }
                }
            }

            $path = $request->file('signed_agreement')->store('agreements', 'private');
            $rent->update(['signed_agreement_path' => $path]);

            return back()->with('success', 'Signed agreement uploaded successfully.');
        }

        return back()->with('error', 'No file uploaded.');
    }

    public function viewUploadedAgreement(Rent $rent)
    {
        $rent->load(['tenant', 'unit.building', 'company.settings']);

        $user = auth()->user();
        $this->authorizeAgreementAccess($user, $rent);

        if (! Schema::hasColumn('rents', 'signed_agreement_path')) {
            return back()->with('error', 'Signed agreement viewing is not available on this deployment yet. Run the signed agreement migration first.');
        }

        $path = $rent->signed_agreement_path;
        $disk = $this->agreementStorageDisk($rent);

        if (! $path || ! $disk) {
            return back()->with('error', 'No signed agreement found.');
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png'], true);
        $isPdf = $extension === 'pdf';

        if (! $isImage && ! $isPdf) {
            return back()->with('error', 'This file type cannot be previewed in the app.');
        }

        $agreementWatermarkText = $this->agreementWatermarkText($user);
        $canRequestAgreementDownload = $this->canManagerRequestAgreementAction($user, $rent, ManagerEditRequest::REQUEST_KIND_AGREEMENT_DOWNLOAD);
        $canRequestAgreementDelete = $this->canManagerRequestAgreementAction($user, $rent, ManagerEditRequest::REQUEST_KIND_AGREEMENT_DELETE);
        $canDownloadUploadedAgreement = $this->canDownloadUploadedAgreement($user, $rent);
        $canDeleteUploadedAgreement = $this->canDeleteUploadedAgreement($user, $rent);

        return view('rents.uploaded-agreement', [
            'rent' => $rent,
            'fileUrl' => route('rents.preview-uploaded-agreement', $rent),
            'fileExtension' => $extension,
            'isPdf' => $isPdf,
            'isImage' => $isImage,
            'agreementWatermarkText' => $agreementWatermarkText,
            'canRequestAgreementDownload' => $canRequestAgreementDownload,
            'canRequestAgreementDelete' => $canRequestAgreementDelete,
            'canDownloadUploadedAgreement' => $canDownloadUploadedAgreement,
            'canDeleteUploadedAgreement' => $canDeleteUploadedAgreement,
        ]);
    }

    public function previewUploadedAgreement(Rent $rent)
    {
        $rent->load(['tenant', 'unit.building', 'company.settings']);

        $user = auth()->user();
        $this->authorizeAgreementAccess($user, $rent);

        if (! Schema::hasColumn('rents', 'signed_agreement_path')) {
            abort(404);
        }

        $path = $rent->signed_agreement_path;
        $disk = $this->agreementStorageDisk($rent);

        if (! $path || ! $disk) {
            abort(404);
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        abort_unless(in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true), 404);

        $mimeType = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';

        return response()->file(Storage::disk($disk)->path($path), [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function downloadAgreement(Rent $rent)
    {
        $rent->load(['tenant', 'unit.building', 'company.settings']);

        $user = auth()->user();
        $this->authorizeAgreementAccess($user, $rent);

        if ($user->isManager() && ! $this->canDownloadUploadedAgreement($user, $rent)) {
            return back()->with('error', ManagerEditRequest::denialMessageFor($user->id, $rent, ManagerEditRequest::REQUEST_KIND_AGREEMENT_DOWNLOAD));
        }

        if (! Schema::hasColumn('rents', 'signed_agreement_path')) {
            return back()->with('error', 'Signed agreement downloads are not available on this deployment yet. Run the signed agreement migration first.');
        }

        $path = $rent->signed_agreement_path;
        $disk = $this->agreementStorageDisk($rent);

        if (! $path || ! $disk) {
            return back()->with('error', 'No signed agreement found.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = 'Tenancy_Agreement_'.str_replace(' ', '_', $rent->tenant->full_name).'.'.$extension;

        return Storage::disk($disk)->download($path, $filename);
    }

    public function deleteUploadedAgreement(Rent $rent)
    {
        $rent->load(['tenant', 'unit.building', 'company.settings']);

        $user = auth()->user();
        $this->authorizeAgreementAccess($user, $rent);

        if ($user->isManager() && ! $this->canDeleteUploadedAgreement($user, $rent)) {
            return back()->with('error', ManagerEditRequest::denialMessageFor($user->id, $rent, ManagerEditRequest::REQUEST_KIND_AGREEMENT_DELETE));
        }

        if (! Schema::hasColumn('rents', 'signed_agreement_path')) {
            return back()->with('error', 'Signed agreement deletion is not available on this deployment yet. Run the signed agreement migration first.');
        }

        $path = $rent->signed_agreement_path;
        $disk = $this->agreementStorageDisk($rent);

        if (! $path || ! $disk) {
            return back()->with('error', 'No signed agreement found.');
        }

        Storage::disk($disk)->delete($path);
        $rent->update(['signed_agreement_path' => null]);

        return redirect()->route('rents.agreement', $rent)->with('success', 'Uploaded agreement file deleted successfully.');
    }

    private function deriveDueDay(string $startDate, string $rentDuration): int
    {
        $start = Carbon::parse($startDate);

        return match ($rentDuration) {
            Rent::DURATION_DAILY => 1,
            Rent::DURATION_WEEKLY => (int) $start->dayOfWeekIso,
            Rent::DURATION_MONTHLY => (int) $start->day,
            default => (int) $start->day,
        };
    }

    private function authorizeAgreementAccess($user, Rent $rent): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isManager()) {
            if (! $rent->unit || ! $rent->unit->building || (int) $rent->unit->building->manager_id !== (int) $user->id) {
                abort(403, 'Unauthorized action.');
            }

            return;
        }

        if (! $rent->tenant || (int) $rent->tenant->user_id !== (int) $user->id) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function agreementWatermarkText($user): ?string
    {
        if (! $user || (! $user->isManager() && ! $user->isAccountant())) {
            return null;
        }

        return AccountIdentityService::watermarkTextFor($user);
    }

    private function canManagerRequestAgreementAction($user, Rent $rent, string $requestKind): bool
    {
        return $user && $user->isManager()
            && ManagerEditRequest::supportsRequestKinds()
            && $rent->signed_agreement_path
            && $this->agreementStorageDisk($rent) !== null;
    }

    private function canDownloadUploadedAgreement($user, Rent $rent): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        return ManagerEditRequest::hasActivePermissionFor($user->id, $rent, ManagerEditRequest::REQUEST_KIND_AGREEMENT_DOWNLOAD);
    }

    private function canDeleteUploadedAgreement($user, Rent $rent): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        return ManagerEditRequest::hasActivePermissionFor($user->id, $rent, ManagerEditRequest::REQUEST_KIND_AGREEMENT_DELETE);
    }

    private function resolveAgreementBackUrl(Request $request, $user, Rent $rent): string
    {
        $sessionKey = 'rent_agreement_back_url_'.$rent->getKey();
        $currentUrl = route('rents.agreement', $rent);
        $previousUrl = url()->previous();

        if ($this->isValidAgreementBackUrl($request, $previousUrl, $currentUrl)) {
            $request->session()->put($sessionKey, $previousUrl);
        }

        return $request->session()->get($sessionKey, $this->defaultAgreementBackUrl($user));
    }

    private function isValidAgreementBackUrl(Request $request, ?string $candidateUrl, string $currentUrl): bool
    {
        if (blank($candidateUrl) || $candidateUrl === $currentUrl) {
            return false;
        }

        if (! Str::startsWith($candidateUrl, $request->getSchemeAndHttpHost())) {
            return false;
        }

        $disallowedFragments = [
            '/manager-edit-requests',
            '/upload-agreement',
            '/download-agreement',
            '/view-uploaded-agreement',
            '/preview-uploaded-agreement',
            '/delete-uploaded-agreement',
        ];

        foreach ($disallowedFragments as $fragment) {
            if (Str::contains($candidateUrl, $fragment)) {
                return false;
            }
        }

        return ! Str::contains($candidateUrl, '/rents/'.$request->route('rent')->getKey().'/agreement');
    }

    private function defaultAgreementBackUrl($user): string
    {
        if ($user?->isAccountant()) {
            return route('accounting.index');
        }

        if ($user?->isTenant()) {
            return route('dashboard');
        }

        return route('dashboard');
    }

    private function agreementStorageDisk(Rent $rent): ?string
    {
        $path = (string) ($rent->signed_agreement_path ?? '');

        if ($path === '') {
            return null;
        }

        if (Storage::disk('private')->exists($path)) {
            return 'private';
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        // Migrate legacy public agreements into private storage the first time
        // an authorized agreement flow touches them.
        Storage::disk('private')->put($path, Storage::disk('public')->get($path));
        Storage::disk('public')->delete($path);

        return 'private';
    }
}
