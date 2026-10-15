<?php

namespace App\Http\Controllers;

use App\Models\AccountingEntry;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Company;
use App\Models\EditRequest;
use App\Models\Payment;
use App\Models\Rent;
use App\Support\CurrentCompany;
use App\Support\RoleRequestNotifier;
use App\Traits\AccountingAuditable;
use App\Support\AuditLogService;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    use AccountingAuditable;

    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if ($user->isSuperAdmin()) {
                abort(403, 'Super admin cannot access workspace financials.');
            }

            if (! $user->isAdmin() && ! $user->isAccountant()) {
                abort(403, 'Unauthorized access to Accounting Module.');
            }

            return $next($request);
        });

        // Desktop only for input/edit actions
        $this->middleware('desktop.only')->only(['storeIncome', 'storeExpense', 'update', 'finalize', 'requestEdit']);
    }

    /**
     * Display the accounting spreadsheet view
     */
    public function index(Request $request)
    {
        EditRequest::expireStaleApprovals();

        $user = auth()->user();
        $accessibleCompanies = $this->getAccessibleCompanies($user);
        $selectedCompanyId = $this->resolveSelectedCompanyId($request->query('company_id'), $accessibleCompanies, $user);
        $statementYears = $this->getStatementYears();
        $selectedStatementYear = $this->resolveStatementYear($request->query('year'), $statementYears);
        $buildingsQuery = Building::with([
            'manager:id,name',
            'units' => function ($query) {
                $query->orderBy('unit_number');
            },
            'units.currentTenant:id,unit_id,full_name,active',
            'units.rents' => function ($query) {
                $query->where('status', 'ACTIVE')
                    ->latest('start_date')
                    ->with('tenant:id,full_name');
            },
        ]);

        if ($selectedCompanyId) {
            $buildingsQuery->where('company_id', $selectedCompanyId);
        }

        $buildings = $buildingsQuery->get();
        $selectedBuildingId = $request->filled('building_id') && $buildings->pluck('id')->contains((int) $request->building_id)
            ? (int) $request->building_id
            : null;

        $entryQuery = AccountingEntry::with(['building', 'creator', 'updater']);
        if (! $user->isAdmin()) {
            $entryQuery->with(['editRequests' => function ($query) use ($user) {
                $query->where('user_id', $user->id)->latest();
            }]);
        }
        $paymentQuery = Payment::with(['creator', 'rent.tenant', 'rent.unit.building']);

        if ($selectedCompanyId) {
            $entryQuery->where('company_id', $selectedCompanyId);
            $paymentQuery->whereHas('rent.unit.building', function ($query) use ($selectedCompanyId) {
                $query->where('company_id', $selectedCompanyId);
            });
        }

        // Filter by building if provided
        if ($selectedBuildingId) {
            $entryQuery->where('building_id', $selectedBuildingId);
            $paymentQuery->whereHas('rent.unit', function ($query) use ($selectedBuildingId) {
                $query->where('building_id', $selectedBuildingId);
            });
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;

            $entryQuery->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('extra_details', 'like', "%{$search}%")
                    ->orWhereHas('building', function ($buildingQuery) use ($search) {
                        $buildingQuery->where('name', 'like', "%{$search}%");
                    });
            });

            $paymentQuery->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhereHas('rent.tenant', function ($tenantQuery) use ($search) {
                        $tenantQuery->where('full_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('rent.unit', function ($unitQuery) use ($search) {
                        $unitQuery->where('unit_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('rent.unit.building', function ($buildingQuery) use ($search) {
                        $buildingQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $records = $this->paginateCollection(
            $this->buildUnifiedRecords($entryQuery->get(), $paymentQuery->get()),
            50,
            $request
        );
        $buildingContexts = $this->buildBuildingContexts($buildings);

        // Accurate Building-level summaries (Including Rent Payments)
        // 1. Get Accounting Entries Summaries
        $accountingSummariesQuery = AccountingEntry::notRejected();
        if ($selectedCompanyId) {
            $accountingSummariesQuery->where('company_id', $selectedCompanyId);
        }
        $accountingSummaries = $accountingSummariesQuery->select('building_id', 'type', DB::raw('SUM(amount) as total'))
            ->groupBy('building_id', 'type')
            ->get();

        // 2. Get Payment Summaries (Rent Income)
        $paymentSummariesQuery = Payment::where('payments.status', 'COMPLETED')
            ->join('rents', 'payments.rent_id', '=', 'rents.id')
            ->join('units', 'rents.unit_id', '=', 'units.id')
            ->join('buildings', 'units.building_id', '=', 'buildings.id');
        if ($selectedCompanyId) {
            $paymentSummariesQuery->where('buildings.company_id', $selectedCompanyId);
        }
        $paymentSummaries = $paymentSummariesQuery
            ->select('units.building_id', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('units.building_id')
            ->pluck('total', 'building_id');

        // 3. Merge into a structure keyed by building_id
        $summaries = $buildings->mapWithKeys(function ($building) use ($accountingSummaries, $paymentSummaries) {
            $accIncome = $accountingSummaries->where('building_id', $building->id)->where('type', 'income')->sum('total');
            $accExpense = $accountingSummaries->where('building_id', $building->id)->where('type', 'expense')->sum('total');
            $rentIncome = $paymentSummaries[$building->id] ?? 0;

            return [$building->id => [
                'income' => $accIncome + $rentIncome,
                'expense' => $accExpense,
                'cashflow' => ($accIncome + $rentIncome) - $accExpense,
            ]];
        });

        // Fetch pending edit requests for Admins
        $pendingEditRequests = collect();
        if ($user->isAdmin()) {
            $pendingEditRequestsQuery = EditRequest::where('status', 'pending')
                ->with(['user', 'accountingEntry.building'])
                ->latest();
            if ($selectedCompanyId) {
                $pendingEditRequestsQuery->whereHas('accountingEntry', function ($query) use ($selectedCompanyId) {
                    $query->where('company_id', $selectedCompanyId);
                });
            }
            $pendingEditRequests = $pendingEditRequestsQuery->get();
        }

        // Fetch recent requests for Reviewers/Managers to show status
        $myEditRequests = collect();
        if (! $user->isAdmin()) {
            $myEditRequestsQuery = EditRequest::where('user_id', $user->id)
                ->with(['accountingEntry.building'])
                ->latest()
                ->take(5);
            if ($selectedCompanyId) {
                $myEditRequestsQuery->whereHas('accountingEntry', function ($query) use ($selectedCompanyId) {
                    $query->where('company_id', $selectedCompanyId);
                });
            }
            $myEditRequests = $myEditRequestsQuery->get();
        }

        return view('accounting.index', compact('records', 'buildings', 'buildingContexts', 'summaries', 'pendingEditRequests', 'myEditRequests', 'statementYears', 'selectedStatementYear', 'accessibleCompanies', 'selectedCompanyId', 'selectedBuildingId'));
    }

    public function report(Request $request)
    {
        $user = auth()->user();
        $buildingId = $request->query('building_id');
        $accessibleCompanies = $this->getAccessibleCompanies($user);
        $selectedCompanyId = $this->resolveSelectedCompanyId($request->query('company_id'), $accessibleCompanies, $user);
        $statementYears = $this->getStatementYears();
        $selectedYear = $this->resolveStatementYear($request->query('year'), $statementYears);

        $query = Building::with(['units', 'rents.unit', 'rents.payments', 'rents.tenant']);
        if ($selectedCompanyId) {
            $query->where('company_id', $selectedCompanyId);
        }
        if ($buildingId) {
            $query->where('id', $buildingId);
        }

        $buildings = $query->get();
        $yearlyAccountingEntries = AccountingEntry::whereIn('building_id', $buildings->pluck('id'))
            ->whereYear('entry_date', $selectedYear)
            ->orderBy('entry_date', 'desc')
            ->get()
            ->groupBy('building_id');

        $singleBuilding = $buildingId && $buildings->count() === 1;

        if ($singleBuilding) {
            $building = $buildings->first();
            $statement = $this->buildBuildingStatementData(
                $building,
                $selectedYear,
                $yearlyAccountingEntries->get($building->id, collect())
            );

            return response()
                ->view('accounting.view-report', [
                    'mode' => 'single',
                    'building' => $building,
                    'rents' => $statement['rents'],
                    'expenses' => $statement['expenses'],
                    'summary' => $statement['summary'],
                    'statement_note' => $statement['statement_note'],
                    'selectedYear' => $selectedYear,
                    'statementYears' => $statementYears,
                    'date' => now()->format('F d, Y'),
                    'generated_by' => $user ? $user->name : 'System',
                ])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('X-Frame-Options', 'DENY')
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('Referrer-Policy', 'no-referrer');
        }

        $reportData = [];
        foreach ($buildings as $building) {
            $statement = $this->buildBuildingStatementData(
                $building,
                $selectedYear,
                $yearlyAccountingEntries->get($building->id, collect())
            );

            $reportData[] = [
                'building' => $building,
                'financials' => $statement['summary'],
                'arrears_details' => collect($statement['rents'])
                    ->filter(function (array $rent) {
                        return $rent['closing_balance'] > 0;
                    })
                    ->map(function (array $rent) {
                        return [
                            'unit' => $rent['unit'],
                            'tenant' => $rent['tenant'],
                            'amount' => $rent['closing_balance'],
                        ];
                    })
                    ->values()
                    ->all(),
            ];
        }

        return response()
            ->view('accounting.view-report', [
                'mode' => 'summary',
                'reportData' => $reportData,
                'selectedYear' => $selectedYear,
                'statementYears' => $statementYears,
                'date' => now()->format('F d, Y'),
                'generated_by' => $user ? $user->name : 'System',
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Frame-Options', 'DENY')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'no-referrer');
    }

    /**
     * Update an existing entry (Admin or Approved Edit Request)
     */
    public function update(Request $request, AccountingEntry $entry)
    {
        EditRequest::expireStaleApprovals();

        $user = auth()->user();

        // Check permissions
        $canEdit = $user->isAdmin();
        $activeRequest = false;
        $directAccountantEdit = false;
        $approvedEditHistory = false;

        if (! $canEdit) {
            // Check for active approved edit request
            $activeRequest = $entry->hasActiveEditPermission($user->id);
            $directAccountantEdit = $entry->canDirectlyEditByAccountant($user->id);
            $approvedEditHistory = EditRequest::query()
                ->where('accounting_entry_id', $entry->id)
                ->where('user_id', $user->id)
                ->whereNotNull('approved_at')
                ->exists();

            if ($activeRequest || $directAccountantEdit) {
                $canEdit = true;
            }
        }

        if (! $canEdit) {
            abort(403, 'You do not have permission to edit this record.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
            'tenant_name' => 'nullable|string|max:200',
            'property_id' => 'nullable|string|max:100',
            'submit_action' => 'nullable|in:save,save_and_lock',
        ]);

        $oldValues = $entry->toArray();

        $updateData = [
            'amount' => $validated['amount'],
            'category' => $validated['category'],
            'entry_date' => $validated['entry_date'],
            'description' => $validated['description'],
            'updated_by' => $user->id,
        ];

        $extraDetails = $entry->extra_details ?? [];
        if (array_key_exists('tenant_name', $validated)) {
            $extraDetails['tenant_name'] = $validated['tenant_name'];
        }
        if (array_key_exists('property_id', $validated)) {
            $extraDetails['property_id'] = $validated['property_id'];
        }
        if (! empty($extraDetails)) {
            $updateData['extra_details'] = $extraDetails;
        }

        $entry->update($updateData);

        self::logAccountingAction('update', $entry->id, 'AccountingEntry', $entry->building_id, $oldValues, $entry->toArray());

        $shouldLockAfterSave = ! $user->isAdmin()
            && ($activeRequest || $directAccountantEdit)
            && ! $entry->isLocked()
            && ($validated['submit_action'] ?? 'save') === 'save_and_lock';

        if ($shouldLockAfterSave) {
            $lockOldValues = $entry->toArray();
            $entry->update([
                'is_locked' => true,
                'status' => 'pending',
                'finalized_at' => null,
            ]);

            if ($activeRequest) {
                EditRequest::where('accounting_entry_id', $entry->id)
                    ->where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->where('expires_at', '>', now())
                    ->update([
                        'expires_at' => now(),
                    ]);
            }

            RoleRequestNotifier::notifyAdmins([
                'title' => 'Accounting entry ready for review',
                'message' => $user->name.' saved changes and locked the expense entry back for admin review.',
                'url' => route('accounting.index'),
                'request_type' => 'accounting_relocked',
                'created_by' => $user->name,
                'subject_id' => $entry->id,
            ], $user->id);

            self::logAccountingAction('lock_after_update', $entry->id, 'AccountingEntry', $entry->building_id, $lockOldValues, $entry->toArray());

            return redirect()->back()->with('success', 'Entry saved and locked back for admin review.');
        }

        if (! $user->isAdmin() && ($activeRequest || $directAccountantEdit) && ! $entry->isLocked()) {
            return redirect()->back()->with('success', 'Entry updated successfully. Please lock this record back now.');
        }

        return redirect()->back()->with('success', 'Entry updated successfully.');
    }

    /**
     * Store a new income entry (Manager/Admin only)
     */
    public function storeIncome(Request $request)
    {
        $user = auth()->user();
        if (! $user->isAdmin() && ! $user->isAccountant()) {
            abort(403, 'Only Accountants or Admins can record income.');
        }

        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
            'tenant_name' => 'nullable|string|max:200',
            'property_id' => 'nullable|string|max:100',
        ]);

        $building = Building::with('manager')->findOrFail($validated['building_id']);

        $entry = AccountingEntry::create([
            'building_id' => $validated['building_id'],
            'type' => 'income',
            'category' => $validated['category'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'entry_date' => $validated['entry_date'],
            'created_by' => $user->id,
            'extra_details' => $this->buildEntryExtraDetails($building, $validated),
        ]);

        self::logAccountingAction('create', $entry->id, 'AccountingEntry', $entry->building_id, null, $entry->toArray());

        return redirect()->back()->with('success', 'Income entry recorded successfully.');
    }

    /**
     * Store a new expense entry (Reviewer/Admin only)
     */
    public function storeExpense(Request $request)
    {
        $user = auth()->user();
        if (! $user->isAdmin() && ! $user->isAccountant()) {
            abort(403, 'Only Accountants or Admins can record expenses.');
        }

        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
            'tenant_name' => 'nullable|string|max:200',
            'property_id' => 'nullable|string|max:100',
        ]);

        $building = Building::with('manager')->findOrFail($validated['building_id']);

        $entry = AccountingEntry::create([
            'building_id' => $validated['building_id'],
            'type' => 'expense',
            'category' => $validated['category'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'entry_date' => $validated['entry_date'],
            'created_by' => $user->id,
            'extra_details' => $this->buildEntryExtraDetails($building, $validated),
        ]);

        self::logAccountingAction('create', $entry->id, 'AccountingEntry', $entry->building_id, null, $entry->toArray());

        return redirect()->back()->with('success', 'Expense entry recorded successfully.');
    }

    /**
     * Finalize an entry (Lock it)
     */
    public function finalize(AccountingEntry $entry)
    {
        EditRequest::expireStaleApprovals();

        $user = auth()->user();

        if (! $user->isAdmin() && ! $user->isAccountant()) {
            abort(403);
        }

        if ($user->isAdmin() && $entry->isInAccountantWorkflow() && ! $entry->isLocked()) {
            return redirect()->back()->with('error', 'Wait for the accountant to lock this entry back before admin review.');
        }

        $activeRequest = false;
        $directAccountantEdit = false;
        if (! $user->isAdmin()) {
            $activeRequest = $entry->hasActiveEditPermission($user->id);
            $directAccountantEdit = $entry->canDirectlyEditByAccountant($user->id);
            $approvedEditHistory = EditRequest::query()
                ->where('accounting_entry_id', $entry->id)
                ->where('user_id', $user->id)
                ->whereNotNull('approved_at')
                ->exists();

            if (! $activeRequest && ! $directAccountantEdit && ! $approvedEditHistory) {
                abort(403, 'You do not have permission to lock this record back.');
            }
        } elseif ($entry->status === 'rejected' && ! $entry->isLocked()) {
            return redirect()->back()->with('error', 'Rejected entries must be corrected and locked back by the accountant before admin review.');
        }

        $oldValues = $entry->toArray();
        $activeApprovedRequests = collect();
        if (! $user->isAdmin()) {
            $activeApprovedRequests = EditRequest::with('admin')
                ->where('accounting_entry_id', $entry->id)
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('expires_at', '>', now())
                ->get();
        }

        $updateData = [
            'is_locked' => true,
        ];

        if ($user->isAdmin()) {
            if ($entry->status !== 'finalized') {
                $updateData['status'] = 'finalized';
                $updateData['finalized_at'] = now();
            }
        } else {
            $updateData['status'] = 'pending';
            $updateData['finalized_at'] = null;
        }

        $entry->update($updateData);

        if (! $user->isAdmin() && $activeRequest) {
            EditRequest::where('accounting_entry_id', $entry->id)
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('expires_at', '>', now())
                ->update([
                    'expires_at' => now(),
                ]);

            $adminsToNotify = $activeApprovedRequests
                ->pluck('admin')
                ->filter();

            if ($adminsToNotify->isNotEmpty()) {
                RoleRequestNotifier::notifyUsers($adminsToNotify, [
                    'title' => 'Accounting record locked back',
                    'message' => $user->name.' updated the accounting record and locked it back for admin review.',
                    'url' => route('accounting.index'),
                    'request_type' => 'accounting_relocked',
                    'created_by' => $user->name,
                    'subject_id' => $entry->id,
                ], $user->id);
            } else {
                RoleRequestNotifier::notifyAdmins([
                    'title' => 'Accounting record locked back',
                    'message' => $user->name.' updated the accounting record and locked it back for admin review.',
                    'url' => route('accounting.index'),
                    'request_type' => 'accounting_relocked',
                    'created_by' => $user->name,
                    'subject_id' => $entry->id,
                ], $user->id);
            }
        } elseif (! $user->isAdmin() && $directAccountantEdit) {
            RoleRequestNotifier::notifyAdmins([
                'title' => 'Accounting entry ready for review',
                'message' => $user->name.' locked the expense entry back for admin review.',
                'url' => route('accounting.index'),
                'request_type' => 'accounting_relocked',
                'created_by' => $user->name,
                'subject_id' => $entry->id,
            ], $user->id);
        }

        self::logAccountingAction('finalize', $entry->id, 'AccountingEntry', $entry->building_id, $oldValues, $entry->toArray());

        return redirect()->back()->with('success', $user->isAdmin() ? 'Entry accepted and locked.' : 'Entry locked successfully and returned to admin for review.');
    }

    /**
     * Reject an expense entry (Admin only)
     */
    public function reject(AccountingEntry $entry)
    {
        $admin = auth()->user();
        if (! $admin->isAdmin()) {
            abort(403);
        }

        if ($entry->type !== 'expense') {
            return redirect()->back()->with('error', 'Only expense entries can be rejected.');
        }

        if ($entry->isInAccountantWorkflow() && ! $entry->isLocked()) {
            return redirect()->back()->with('error', 'Wait for the accountant to lock this entry back before rejecting it.');
        }

        if ($entry->status === 'rejected') {
            return redirect()->back()->with('error', 'This expense entry has already been rejected and is awaiting an edit request or correction.');
        }

        $oldValues = $entry->toArray();
        $entry->update([
            'status' => 'rejected',
            'is_locked' => true,
            'finalized_at' => null,
        ]);

        $accountantsToNotify = collect([$entry->updater, $entry->creator])
            ->filter(function ($user) {
                return $user instanceof \App\Models\User && $user->isAccountant();
            });

        if ($accountantsToNotify->isNotEmpty()) {
            RoleRequestNotifier::notifyUsers($accountantsToNotify, [
                'title' => 'Expense entry rejected',
                'message' => $admin->name.' rejected the expense entry. Submit an edit request so admin can unlock it for correction.',
                'url' => route('accounting.index'),
                'request_type' => 'accounting_rejected',
                'created_by' => $admin->name,
                'subject_id' => $entry->id,
            ], $admin->id);
        }

        self::logAccountingAction('reject_entry', $entry->id, 'AccountingEntry', $entry->building_id, $oldValues, $entry->toArray());

        return redirect()->back()->with('success', 'Expense entry rejected. The accountant must request edit access before it can be corrected.');
    }

    /**
     * Unlock an entry (Admin only)
     */
    public function unlock(AccountingEntry $entry)
    {
        $admin = auth()->user();
        if (! $admin->isAdmin()) {
            abort(403);
        }

        $oldValues = $entry->toArray();
        $entry->update(['is_locked' => false]);

        $approvedRequests = EditRequest::with('user')
            ->where('accounting_entry_id', $entry->id)
            ->where('status', 'approved')
            ->where('expires_at', '>', now())
            ->get();

        $approvedRequests->each(function (EditRequest $editRequest) use ($admin, $entry) {
            RoleRequestNotifier::notifyUser($editRequest->user, [
                'title' => 'Accounting record unlocked',
                'message' => $admin->name.' unlocked the accounting record you requested to edit.',
                'url' => route('accounting.index'),
                'request_type' => 'accounting_unlocked',
                'created_by' => $admin->name,
                'subject_id' => $entry->id,
            ], $admin->id);
        });

        self::logAccountingAction('unlock', $entry->id, 'AccountingEntry', $entry->building_id, $oldValues, $entry->toArray());

        return redirect()->back()->with('success', 'Entry unlocked successfully. The accountant should edit it and lock it back when done.');
    }

    /**
     * Request edit permission for a locked record (Reviewer/Manager)
     */
    public function requestEdit(Request $request, AccountingEntry $entry)
    {
        EditRequest::expireStaleApprovals();

        $user = auth()->user();

        if ($user->isAdmin()) {
            abort(403);
        }

        if (! $entry->isLocked()) {
            return redirect()->back()->with('error', 'This record is already unlocked for editing.');
        }

        $existingPendingRequest = EditRequest::pending()
            ->where('accounting_entry_id', $entry->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingPendingRequest) {
            return redirect()->back()->with('error', 'Edit already requested for this record.');
        }

        $existingApprovedRequest = EditRequest::where('accounting_entry_id', $entry->id)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingApprovedRequest) {
            return redirect()->back()->with('error', 'Your edit request for this record has already been approved.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $editRequest = EditRequest::create([
            'accounting_entry_id' => $entry->id,
            'user_id' => $user->id,
            'reason' => $validated['reason'],
        ]);

        RoleRequestNotifier::notifyAdmins([
            'title' => 'Accounting edit request',
            'message' => $user->name.' requested admin approval to edit a locked accounting entry.',
            'url' => route('accounting.index'),
            'request_type' => 'accounting_edit',
            'created_by' => $user->name,
            'subject_id' => $editRequest->id,
        ], $user->id);

        self::logAccountingAction('request_edit', $entry->id, 'AccountingEntry', $entry->building_id, null, ['request_id' => $editRequest->id]);

        return redirect()->back()->with('success', 'Edit request submitted for Admin approval.');
    }

    /**
     * Approve or Reject an edit request (Admin only)
     */
    public function handleEditRequest(Request $request, EditRequest $editRequest)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        EditRequest::expireStaleApprovals();

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'duration_value' => 'nullable|integer|min:1|max:168',
            'duration_unit' => 'nullable|in:minutes,hours,days',
        ]);

        if ($editRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This edit request has already been reviewed.');
        }

        if ($validated['action'] === 'approve') {
            $expiresAt = $this->resolveEditRequestExpiry(
                (int) ($validated['duration_value'] ?? EditRequest::DEFAULT_DURATION_VALUE),
                $validated['duration_unit'] ?? EditRequest::DEFAULT_DURATION_UNIT
            );

            $editRequest->update([
                'status' => 'approved',
                'admin_id' => auth()->id(),
                'approved_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            RoleRequestNotifier::notifyUser($editRequest->user, [
                'title' => 'Accounting edit request approved',
                'message' => auth()->user()->name.' approved your accounting edit request. You can edit the record until '.$expiresAt->format('d M Y, h:i A').'.',
                'url' => route('accounting.index'),
                'request_type' => 'accounting_edit_approved',
                'created_by' => auth()->user()->name,
                'subject_id' => $editRequest->id,
            ], auth()->id());

            self::logAccountingAction('approve_edit', $editRequest->accounting_entry_id, 'AccountingEntry', null, null, ['request_id' => $editRequest->id]);

            return redirect()->back()->with('success', 'Edit request approved until '.$expiresAt->format('d M Y, h:i A').'.');
        } else {
            $editRequest->update([
                'status' => 'rejected',
                'admin_id' => auth()->id(),
            ]);

            RoleRequestNotifier::notifyUser($editRequest->user, [
                'title' => 'Accounting edit request rejected',
                'message' => auth()->user()->name.' rejected your accounting edit request.',
                'url' => route('accounting.index'),
                'request_type' => 'accounting_edit_rejected',
                'created_by' => auth()->user()->name,
                'subject_id' => $editRequest->id,
            ], auth()->id());

            self::logAccountingAction('reject_edit', $editRequest->accounting_entry_id, 'AccountingEntry', null, null, ['request_id' => $editRequest->id]);

            return redirect()->back()->with('success', 'Edit request rejected.');
        }
    }

    /**
     * Display the audit trail (Admin only)
     */
    public function auditTrail()
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $logs = AuditLog::with(['user', 'building'])->latest()->paginate(100);

        return view('accounting.audit-trail', compact('logs'));
    }

    /**
     * Export Financial Report (CSV)
     */
    public function export()
    {
        $user = auth()->user();
        if (! $user || ! $user->isAdmin()) {
            return response('Access denied. Only administrators can export reports.', 403);
        }

        try {
            app(AuditLogService::class)->log('report_export_csv', null, null, [
                'year' => request()->query('year'),
                'building_id' => request()->query('building_id'),
                'company_id' => request()->query('company_id'),
            ]);
            $buildingId = request()->query('building_id');
            $accessibleCompanies = $this->getAccessibleCompanies($user);
            $selectedCompanyId = $this->resolveSelectedCompanyId(request()->query('company_id'), $accessibleCompanies, $user);
            $statementYears = $this->getStatementYears();
            $selectedYear = $this->resolveStatementYear(request()->query('year'), $statementYears);
            $query = Building::with(['units', 'rents.unit', 'rents.payments', 'rents.tenant']);
            if ($selectedCompanyId) {
                $query->where('company_id', $selectedCompanyId);
            }

            if ($buildingId) {
                $query->where('id', $buildingId);
                $singleBuilding = true;
            } else {
                $singleBuilding = false;
            }

            $buildings = $query->get();
            $yearlyAccountingEntries = AccountingEntry::notRejected()->whereIn('building_id', $buildings->pluck('id'))
                ->whereYear('entry_date', $selectedYear)
                ->orderBy('entry_date', 'desc')
                ->get()
                ->groupBy('building_id');

            // Clean buffer to prevent "headers already sent" or garbage data
            if (ob_get_level()) {
                ob_end_clean();
            }

            $filename = 'financial-summary-'.$selectedYear.'-'.date('Y-m-d-His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $columns = [
                'Building Name',
                'Selected Year',
                'Total Income (Payments + Other)',
                'Total Expenses',
                'Net Cash Flow',
                'Closing Outstanding',
                'Occupancy Rate (%)',
            ];

            $callback = function () use ($columns, $buildings, $singleBuilding, $selectedYear, $yearlyAccountingEntries) {
                try {
                    $file = fopen('php://output', 'w');

                    // Add UTF-8 BOM for Excel compatibility
                    fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                    fputcsv($file, $columns);

                    foreach ($buildings as $building) {
                        $statement = $this->buildBuildingStatementData(
                            $building,
                            $selectedYear,
                            $yearlyAccountingEntries->get($building->id, collect())
                        );

                        // Calculate Occupancy
                        $totalUnits = $building->units->count();
                        $occupiedUnits = $building->units->where('status', 'OCCUPIED')->count();
                        $occupancy = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

                        fputcsv($file, [
                            $building->name,
                            $selectedYear,
                            number_format($statement['summary']['income'], 2, '.', ''),
                            number_format($statement['summary']['expense'], 2, '.', ''),
                            number_format($statement['summary']['cashflow'], 2, '.', ''),
                            number_format($statement['summary']['outstanding'], 2, '.', ''),
                            $occupancy.'%',
                        ]);

                        // If generating a report for a SINGLE building, output detailed sections below the summary row
                        if ($singleBuilding) {
                            fputcsv($file, []); // Spacer
                            fputcsv($file, ['TENANT YEARLY STATEMENT']);
                            fputcsv($file, [$statement['statement_note']]);
                            fputcsv($file, ['Unit', 'Tenant Name', 'Start Date', 'Rent Amount', 'Rent Duration', 'Previous Year Outstanding', 'Current Year Charge', 'Payments This Year', 'Total Dealings', 'Closing Balance']);

                            foreach ($statement['rents'] as $rent) {
                                fputcsv($file, [
                                    $rent['unit'],
                                    $rent['tenant'],
                                    $rent['start_date'] ? $rent['start_date']->format('Y-m-d') : 'N/A',
                                    number_format($rent['rent_amount'] ?? $rent['annual_rent'] ?? 0, 2, '.', ''),
                                    $rent['rent_duration'] ?? 'Annually',
                                    number_format($rent['opening_outstanding'], 2, '.', ''),
                                    number_format($rent['current_year_charge'], 2, '.', ''),
                                    number_format($rent['payments_this_year'], 2, '.', ''),
                                    number_format($rent['total_dealings'], 2, '.', ''),
                                    number_format($rent['closing_balance'], 2, '.', ''),
                                ]);
                            }

                            fputcsv($file, []); // Spacer
                            fputcsv($file, ['EXPENSES BREAKDOWN FOR '.$selectedYear]);
                            fputcsv($file, ['Date', 'Category', 'Description', 'Amount']);

                            foreach ($statement['expenses'] as $expense) {
                                fputcsv($file, [
                                    $expense->entry_date->format('Y-m-d'),
                                    $expense->category,
                                    $expense->description,
                                    number_format($expense->amount, 2, '.', ''),
                                ]);
                            }
                        }

                    }

                    fclose($file);
                } catch (\Throwable $e) {
                    error_log('CSV export failed: '.$e->getMessage());
                    echo 'Export failed. Please try again later.';
                }
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            try {
                \Log::error('CSV export failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            } catch (\Throwable $ignored) {
                error_log('CSV export failed (logger unavailable): '.$e->getMessage());
            }

            return response('Export failed. Please try again later.', 500);
        }
    }

    /**
     * Export Financial Report (PDF)
     */
    public function exportPdf()
    {
        $user = auth()->user();
        if (! $user || ! $user->isAdmin()) {
            return response('Access denied. Only administrators can export reports.', 403);
        }

        try {
            app(AuditLogService::class)->log('report_export_pdf', null, null, [
                'year' => request()->query('year'),
                'building_id' => request()->query('building_id'),
                'company_id' => request()->query('company_id'),
            ]);
            if (! app()->bound('dompdf.wrapper')) {
                try {
                    app()->register(ServiceProvider::class);
                } catch (\Throwable $e) {
                    error_log('DomPDF provider registration failed: '.$e->getMessage());
                }
            }

            if (! app()->bound('dompdf.wrapper')) {
                return response('PDF export is temporarily unavailable on this server.', 500);
            }

            $statementYears = $this->getStatementYears();
            $selectedYear = $this->resolveStatementYear(request()->query('year'), $statementYears);
            $accessibleCompanies = $this->getAccessibleCompanies($user);
            $selectedCompanyId = $this->resolveSelectedCompanyId(request()->query('company_id'), $accessibleCompanies, $user);
            $query = Building::with(['units', 'rents.unit', 'rents.payments', 'rents.tenant']);
            if ($selectedCompanyId) {
                $query->where('company_id', $selectedCompanyId);
            }

            if (request()->has('building_id') && request()->building_id) {
                $query->where('id', request()->building_id);
                $singleBuilding = true;
            } else {
                $singleBuilding = false;
            }

            $buildings = $query->get();
            $yearlyAccountingEntries = AccountingEntry::notRejected()->whereIn('building_id', $buildings->pluck('id'))
                ->whereYear('entry_date', $selectedYear)
                ->orderBy('entry_date', 'desc')
                ->get()
                ->groupBy('building_id');

            // IF SINGLE BUILDING: Generate Detailed Report
            if ($singleBuilding && $buildings->count() === 1) {
                $building = $buildings->first();
                $statement = $this->buildBuildingStatementData(
                    $building,
                    $selectedYear,
                    $yearlyAccountingEntries->get($building->id, collect())
                );

                $pdf = PdfFacade::loadView('accounting.pdf-detailed-report', [
                    'building' => $building,
                    'rents' => $statement['rents'],
                    'expenses' => $statement['expenses'],
                    'summary' => $statement['summary'],
                    'statement_note' => $statement['statement_note'],
                    'selectedYear' => $selectedYear,
                    'date' => now()->format('F d, Y'),
                    'generated_by' => $user->name,
                ]);

                return $pdf->download('building-report-'.$building->id.'-'.$selectedYear.'-'.now()->format('Y-m-d').'.pdf');
            }

            // ELSE: Generate Summary Report (Existing Logic)
            $reportData = [];

            foreach ($buildings as $building) {
                $statement = $this->buildBuildingStatementData(
                    $building,
                    $selectedYear,
                    $yearlyAccountingEntries->get($building->id, collect())
                );

                $reportData[] = [
                    'building' => $building,
                    'financials' => $statement['summary'],
                    'arrears_details' => collect($statement['rents'])
                        ->filter(function (array $rent) {
                            return $rent['closing_balance'] > 0;
                        })
                        ->map(function (array $rent) {
                            return [
                                'unit' => $rent['unit'],
                                'tenant' => $rent['tenant'],
                                'amount' => $rent['closing_balance'],
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            }

            $pdf = PdfFacade::loadView('accounting.pdf-report', [
                'reportData' => $reportData,
                'selectedYear' => $selectedYear,
                'date' => now()->format('F d, Y'),
                'generated_by' => $user->name,
            ]);

            return $pdf->download('financial-report-'.$selectedYear.'-'.now()->format('Y-m-d').'.pdf');
        } catch (\Throwable $e) {
            try {
                \Log::error('PDF export failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            } catch (\Throwable $ignored) {
                error_log('PDF export failed (logger unavailable): '.$e->getMessage());
            }

            return response('Export failed. Please try again later.', 500);
        }
    }

    private function buildUnifiedRecords(Collection $accountingEntries, Collection $payments): Collection
    {
        $entryRecords = $accountingEntries->map(function (AccountingEntry $entry) {
            $isUnlockedForEdit = $entry->status === 'finalized' && ! $entry->is_locked;
            $isRejectedLocked = $entry->status === 'rejected' && $entry->is_locked;
            $isRejectedUnlocked = $entry->status === 'rejected' && ! $entry->is_locked;
            $isPendingReview = $entry->status === 'pending' && $entry->is_locked;
            $isPendingDraft = $entry->status === 'pending' && ! $entry->is_locked;

            if ($isRejectedLocked) {
                $statusLabel = 'Rejected - Request Edit';
                $statusBadgeClass = 'bg-danger';
            } elseif ($isRejectedUnlocked) {
                $statusLabel = 'Rejected - Unlocked For Correction';
                $statusBadgeClass = 'bg-warning text-dark';
            } elseif ($isUnlockedForEdit) {
                $statusLabel = 'Unlocked For Edit';
                $statusBadgeClass = 'bg-warning text-dark';
            } elseif ($isPendingReview) {
                $statusLabel = 'Awaiting Admin Review';
                $statusBadgeClass = 'bg-info text-dark';
            } elseif ($entry->status === 'finalized') {
                $statusLabel = 'Finalized';
                $statusBadgeClass = 'bg-primary';
            } else {
                $statusLabel = $isPendingDraft ? 'Draft - Unlocked' : 'Pending';
                $statusBadgeClass = 'bg-warning text-dark';
            }

            return [
                'source' => 'accounting',
                'key' => 'entry-'.$entry->id,
                'row_class' => $entry->type === 'income' ? 'type-income' : 'type-expense',
                'type_label' => ucfirst($entry->type),
                'type_badge_class' => $entry->type === 'income' ? 'bg-success' : 'bg-danger',
                'amount_prefix' => $entry->type === 'income' ? '+' : '-',
                'amount_class' => $entry->type === 'income' ? 'text-success' : 'text-danger',
                'date' => $entry->entry_date,
                'building_name' => optional($entry->building)->name ?? 'N/A',
                'category' => $entry->category,
                'status_label' => $statusLabel,
                'status_badge_class' => $statusBadgeClass,
                'model' => $entry,
            ];
        });

        $paymentRecords = $payments->map(function (Payment $payment) {
            $paymentStatus = strtoupper((string) $payment->status);
            $typeBadgeClass = match ($paymentStatus) {
                'PENDING' => 'bg-warning text-dark',
                'FAILED' => 'bg-secondary',
                default => 'bg-success',
            };
            $statusBadgeClass = match ($paymentStatus) {
                'PENDING' => 'bg-warning text-dark',
                'FAILED' => 'bg-danger',
                default => 'bg-success',
            };

            return [
                'source' => 'payment',
                'key' => 'payment-'.$payment->id,
                'row_class' => 'type-income',
                'type_label' => 'Payment',
                'type_badge_class' => $typeBadgeClass,
                'amount_prefix' => '+',
                'amount_class' => $paymentStatus === 'FAILED' ? 'text-muted' : 'text-success',
                'date' => $payment->payment_date,
                'building_name' => optional(optional(optional($payment->rent)->unit)->building)->name ?? 'N/A',
                'category' => 'Rent Payment',
                'status_label' => ucfirst(strtolower($paymentStatus ?: 'completed')),
                'status_badge_class' => $statusBadgeClass,
                'model' => $payment,
            ];
        });

        return $entryRecords->concat($paymentRecords)
            ->sort(function (array $left, array $right) {
                $dateCompare = $right['date']->timestamp <=> $left['date']->timestamp;
                if ($dateCompare !== 0) {
                    return $dateCompare;
                }

                return $right['model']->id <=> $left['model']->id;
            })
            ->values();
    }

    private function paginateCollection(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function buildBuildingContexts(Collection $buildings): array
    {
        return $buildings->mapWithKeys(function (Building $building) {
            $units = $building->units->map(function ($unit) {
                $activeRent = $unit->rents->first();
                $tenant = optional($activeRent)->tenant ?: $unit->currentTenant;
                $label = $unit->unit_number.($unit->floor ? ' - Floor '.$unit->floor : '');

                return [
                    'id' => $unit->id,
                    'property_id' => $unit->unit_number,
                    'label' => $label,
                    'status' => $unit->status,
                    'tenant_name' => $tenant?->full_name,
                ];
            })->values();

            $tenantOptions = $units
                ->filter(function (array $unit) {
                    return ! empty($unit['tenant_name']);
                })
                ->map(function (array $unit) {
                    return [
                        'name' => $unit['tenant_name'],
                        'property_id' => $unit['property_id'],
                        'unit_id' => $unit['id'],
                        'label' => $unit['tenant_name'].' - '.$unit['label'],
                    ];
                })
                ->unique(function (array $tenant) {
                    return $tenant['name'].'|'.$tenant['property_id'];
                })
                ->values();

            return [
                $building->id => [
                    'id' => $building->id,
                    'name' => $building->name,
                    'address' => $building->address,
                    'manager_name' => optional($building->manager)->name ?? 'Unassigned',
                    'total_units' => (int) $building->units->count(),
                    'occupied_units' => (int) $building->units->where('status', 'OCCUPIED')->count(),
                    'unit_options' => $units,
                    'tenant_options' => $tenantOptions,
                ],
            ];
        })->all();
    }

    private function buildEntryExtraDetails(Building $building, array $validated): array
    {
        return array_filter([
            'tenant_name' => $validated['tenant_name'] ?? null,
            'property_id' => $validated['property_id'] ?? null,
            'building_address' => $building->address,
            'manager_name' => optional($building->manager)->name ?? 'Unassigned',
        ], function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function resolveEditRequestExpiry(int $durationValue, string $durationUnit): Carbon
    {
        $baseTime = now();

        return match ($durationUnit) {
            'minutes' => $baseTime->addMinutes($durationValue),
            'days' => $baseTime->addDays($durationValue),
            default => $baseTime->addHours($durationValue),
        };
    }

    private function getStatementYears(): array
    {
        $currentYear = (int) now()->year;
        $dateCandidates = [
            Rent::min('start_date'),
            Payment::min('payment_date'),
            AccountingEntry::min('entry_date'),
        ];

        $years = collect($dateCandidates)
            ->filter()
            ->map(function ($date) {
                return Carbon::parse($date)->year;
            })
            ->push($currentYear)
            ->sort()
            ->values();

        $startYear = $years->first() ?: $currentYear;

        return array_reverse(range($startYear, $currentYear));
    }

    private function getAccessibleCompanies($user): Collection
    {
        if ($user->isSuperAdmin()) {
            return Company::query()
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        if (! $user->company_id) {
            return collect();
        }

        return Company::query()
            ->whereKey($user->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function resolveSelectedCompanyId(?string $companyId, Collection $accessibleCompanies, $user): ?int
    {
        if ($accessibleCompanies->isEmpty()) {
            return $user->company_id ? (int) $user->company_id : null;
        }

        $resolvedCompanyId = $companyId !== null && $companyId !== '' ? (int) $companyId : null;
        if ($resolvedCompanyId && $accessibleCompanies->pluck('id')->contains($resolvedCompanyId)) {
            return $resolvedCompanyId;
        }

        if (! $user->isSuperAdmin() && $user->company_id) {
            return (int) $user->company_id;
        }

        $currentCompanyId = app(CurrentCompany::class)->id();
        if ($currentCompanyId && $accessibleCompanies->pluck('id')->contains($currentCompanyId)) {
            return (int) $currentCompanyId;
        }

        return (int) $accessibleCompanies->sortBy('id')->first()->id;
    }

    private function resolveStatementYear(?string $year, array $availableYears = []): int
    {
        $fallbackYear = $availableYears[0] ?? (int) now()->year;

        if (! $year || ! is_numeric($year)) {
            return $fallbackYear;
        }

        $resolvedYear = (int) $year;

        if (! empty($availableYears) && ! in_array($resolvedYear, $availableYears, true)) {
            return $fallbackYear;
        }

        return $resolvedYear;
    }

    private function buildBuildingStatementData(Building $building, int $year, Collection $accountingEntries): array
    {
        $periodStart = Carbon::create($year, 1, 1)->startOfDay();
        $periodEnd = Carbon::create($year, 12, 31)->endOfDay();
        $rents = collect($building->rents)
            ->map(function (Rent $rent) use ($periodStart, $periodEnd) {
                return $this->buildRentStatementData($rent, $periodStart, $periodEnd);
            })
            ->filter()
            ->values();

        $rents = $this->filterReplacedTenantStatements($rents)
            ->sortBy('unit')
            ->values()
            ->all();

        $reportEntries = $accountingEntries
            ->where('status', '!=', 'rejected');
        $expenses = $reportEntries
            ->where('type', 'expense')
            ->sortByDesc('entry_date')
            ->values();
        $otherIncome = $reportEntries
            ->where('type', 'income')
            ->sum('amount');
        $rentIncome = collect($rents)->sum('payments_this_year');
        $expenseTotal = $expenses->sum('amount');
        $totalOutstanding = collect($rents)->sum(function (array $rent) {
            return max((float) $rent['closing_balance'], 0);
        });

        return [
            'rents' => $rents,
            'expenses' => $expenses,
            'summary' => [
                'income' => $otherIncome + $rentIncome,
                'expense' => $expenseTotal,
                'cashflow' => ($otherIncome + $rentIncome) - $expenseTotal,
                'outstanding' => $totalOutstanding,
            ],
            'statement_note' => 'This statement shows each tenant payment recorded in '.$year.', any outstanding brought forward from previous years, rent charges accrued only on their exact due dates up to the statement cutoff, total dealings, and the closing balance for the selected year.',
        ];
    }

    private function buildRentStatementData(Rent $rent, Carbon $periodStart, Carbon $periodEnd): ?array
    {
        $startDate = $rent->start_date;
        if (! $startDate) {
            return null;
        }

        $calculationEnd = $this->determineRentCalculationEnd($rent, $periodEnd);
        if (! $calculationEnd || $startDate->gt($calculationEnd)) {
            return null;
        }

        $year = $periodStart->year;
        $payments = $rent->payments
            ->filter(function (Payment $payment) {
                return strtoupper((string) $payment->status) === 'COMPLETED' && $payment->payment_date;
            })
            ->sortBy('payment_date')
            ->values();

        $paymentsBeforeYear = $payments
            ->filter(function (Payment $payment) use ($periodStart) {
                return $payment->payment_date->lt($periodStart);
            })
            ->sum('amount');
        $paymentsThisYear = $payments
            ->filter(function (Payment $payment) use ($periodStart, $calculationEnd) {
                return $payment->payment_date->betweenIncluded($periodStart, $calculationEnd);
            })
            ->sum('amount');

        $openingCutoff = $periodStart->copy()->subDay()->endOfDay();
        $dueBeforeYear = $openingCutoff->lt($startDate)
            ? 0
            : $this->countDueCyclesBetween($rent, $startDate->copy(), $openingCutoff);
        $dueThisYear = $calculationEnd->lt($periodStart)
            ? 0
            : $this->countDueCyclesBetween($rent, $periodStart, $calculationEnd);
        $openingBalance = ($dueBeforeYear * (float) $rent->annual_amount) - $paymentsBeforeYear;
        $currentYearCharge = $dueThisYear * (float) $rent->annual_amount;
        $closingBalance = $openingBalance + $currentYearCharge - $paymentsThisYear;
        $totalDealings = max($openingBalance, 0) + $currentYearCharge;

        if ($openingBalance == 0.0 && $currentYearCharge == 0.0 && $paymentsThisYear == 0.0 && $closingBalance == 0.0) {
            return null;
        }

        return [
            'rent_id' => $rent->id,
            'unit_id' => $rent->unit_id,
            'unit' => $this->formatUnitLabel($rent->unit),
            'tenant' => $rent->tenant ? $rent->tenant->full_name : 'No Tenant Assigned',
            'tenant_active' => (bool) optional($rent->tenant)->active,
            'rent_status' => strtoupper((string) $rent->status),
            'rent_amount' => (float) $rent->annual_amount,
            'rent_amount_label' => $rent->rent_amount_label,
            'rent_duration' => $rent->rent_duration_label,
            'opening_outstanding' => max($openingBalance, 0),
            'current_year_charge' => $currentYearCharge,
            'payments_this_year' => $paymentsThisYear,
            'total_dealings' => $totalDealings,
            'closing_balance' => $closingBalance,
            'start_date' => $startDate,
        ];
    }

    private function filterReplacedTenantStatements(Collection $rents): Collection
    {
        return $rents
            ->groupBy('unit_id')
            ->map(function (Collection $unitRents) {
                if ($unitRents->count() === 1) {
                    return $unitRents->first();
                }

                $preferred = $unitRents
                    ->filter(function (array $rent) {
                        return $rent['tenant_active'] || $rent['rent_status'] === 'ACTIVE';
                    })
                    ->sortByDesc('start_date')
                    ->first();

                if ($preferred) {
                    return $preferred;
                }

                return $unitRents
                    ->sortByDesc('start_date')
                    ->first();
            })
            ->values();
    }

    private function countDueCyclesBetween(Rent $rent, Carbon $periodStart, Carbon $periodEnd): int
    {
        return $rent->countDueCyclesBetween($periodStart, $periodEnd);
    }

    private function determineRentCalculationEnd(Rent $rent, Carbon $periodEnd): ?Carbon
    {
        $calculationEnd = $periodEnd->copy()->min(now()->endOfDay());

        if ($rent->end_date) {
            $calculationEnd = $calculationEnd->min($rent->end_date->copy()->endOfDay());
        }

        return $calculationEnd;
    }

    private function formatUnitLabel($unit): string
    {
        if (! $unit) {
            return 'Unknown Unit';
        }

        return $unit->unit_number.($unit->floor ? ' (Floor '.$unit->floor.')' : '');
    }
}
