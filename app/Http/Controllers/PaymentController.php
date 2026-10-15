<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Rent;
use App\Models\User;
use App\Models\ManagerEditRequest;
use App\Support\AuditLogService;
use App\Support\RoleRequestNotifier;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->user()?->isSuperAdmin()) {
                abort(403, 'Super admin cannot access workspace financials.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        abort_unless(auth()->user()->hasPermission('payments.view'), 403);

        $query = Payment::with(['rent.tenant', 'rent.unit.building', 'creator'])->latest('payment_date');
        if (auth()->user()->isManager()) {
            $query->whereHas('rent.unit.building', function ($q) {
                $q->where('manager_id', auth()->id());
            });
        }
        $payments = $query->paginate(20);

        return view('payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('payments.create'), 403);

        $rentId = $request->query('rent_id');
        $rentsQuery = Rent::with(['tenant', 'unit.building', 'payments'])->whereHas('tenant', function ($q) {
            $q->where('active', true);
        });
        if (auth()->user()->isManager()) {
            $rentsQuery->whereHas('unit.building', function ($q) {
                $q->where('manager_id', auth()->id());
            });
        }
        $rents = $rentsQuery->get();

        $selectedRent = $rentId ? Rent::with('payments')->find($rentId) : null;

        return view('payments.create', compact('rents', 'selectedRent'));
    }

    public function store(Request $request)
    {
        $actor = auth()->user();
        abort_unless($actor->hasPermission('payments.create'), 403);
        $validated = $request->validate([
            'rent_id' => 'required|exists:rents,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'status' => 'nullable|in:COMPLETED,PENDING,FAILED',
        ]);

        // Automatically set due_date to match the end of the lease date
        $rent = Rent::with(['tenant', 'unit.building'])->find($validated['rent_id']);
        if ($actor->isManager()) {
            if (! $rent || ! $rent->unit || ! $rent->unit->building || $rent->unit->building->manager_id !== $actor->id) {
                abort(403, 'Unauthorized action.');
            }
        }
        if ($rent && $rent->end_date) {
            $validated['due_date'] = $rent->end_date;
        }

        $payment = Payment::create($validated);
        app(AuditLogService::class)->log('payment_created', $payment, null, $payment->toArray());

        if ($actor && ($actor->isAdmin() || $actor->isManager())) {
            $accountants = User::query()
                ->whereHas('role', function ($query) {
                    $query->whereRaw('LOWER(name) = ?', ['accountant']);
                })
                ->get();

            if ($accountants->isNotEmpty()) {
                $tenantName = optional($rent->tenant)->full_name ?? 'a tenant';
                $buildingName = optional(optional($rent->unit)->building)->name;

                RoleRequestNotifier::notifyUsers($accountants, [
                    'title' => 'Payment recorded',
                    'message' => $actor->name.' recorded a payment for '.$tenantName.($buildingName ? ' in '.$buildingName : '').'.',
                    'url' => route('accounting.index'),
                    'request_type' => 'payment_recorded',
                    'created_by' => $actor->name,
                    'subject_id' => $payment->id,
                ], $actor->id);
            }
        }

        return redirect()->route('buildings.show', $rent->unit->building_id)->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment)
    {
        abort_unless(auth()->user()->hasPermission('payments.view'), 403);
        $payment->load('rent.tenant', 'rent.unit.building', 'creator');

        return view('payments.show', compact('payment'));
    }

    public function downloadReceipt(Request $request, Payment $payment)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('payments.view'), 403);
        $payment->loadMissing(['company.settings', 'rent.company.settings', 'rent.tenant.user', 'rent.unit.building.company.settings', 'creator']);

        abort_unless($this->canDownloadReceipt($user, $payment), 403, 'You are not allowed to download this receipt.');

        if (strtoupper((string) $payment->status) !== 'COMPLETED') {
            return back()->with('error', 'Only completed payments can generate a receipt.');
        }

        $receiptNumber = $this->buildReceiptNumber($payment);
        $backUrl = $request->headers->get('referer') ?: route('payments.index');
        $viewData = [
            'payment' => $payment,
            'receiptNumber' => $receiptNumber,
            'issuedTo' => optional($payment->rent->tenant)->full_name ?? 'Tenant',
            'generatedBy' => $user->name,
            'backUrl' => $backUrl,
            'renderMode' => 'web',
        ];

        if ($request->boolean('download')) {
            if (! app()->bound('dompdf.wrapper')) {
                app()->register(ServiceProvider::class);
            }

            $viewData['renderMode'] = 'pdf';

            return PdfFacade::loadView('payments.receipt', $viewData)
                ->setPaper('a4')
                ->download('payment-receipt-'.$payment->id.'.pdf');
        }

        return response()->view('payments.receipt', $viewData);
    }

    public function edit(Payment $payment)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('payments.edit'), 403);

        if ($user->isManager()) {
            abort_unless((int) optional(optional(optional($payment->rent)->unit)->building)->manager_id === (int) $user->id, 403, 'Unauthorized action.');
            abort_unless(
                ManagerEditRequest::hasActivePermissionFor($user->id, $payment),
                403,
                ManagerEditRequest::denialMessageFor($user->id, $payment)
            );
        } else {
            abort_unless($user->isAdmin(), 403, 'Managers cannot edit payments directly. Request admin edit instead.');
        }

        $rents = Rent::with(['tenant', 'unit.building'])
            ->when($user->isManager(), function ($query) use ($user, $payment) {
                $query->where(function ($rentQuery) use ($user, $payment) {
                    $rentQuery
                        ->whereHas('unit.building', function ($buildingQuery) use ($user) {
                            $buildingQuery->where('manager_id', $user->id);
                        })
                        ->orWhere('id', $payment->rent_id);
                });
            })
            ->get();

        return view('payments.edit', compact('payment', 'rents'));
    }

    public function update(Request $request, Payment $payment)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('payments.edit'), 403);

        if ($user->isManager()) {
            abort_unless((int) optional(optional(optional($payment->rent)->unit)->building)->manager_id === (int) $user->id, 403, 'Unauthorized action.');
            abort_unless(
                ManagerEditRequest::hasActivePermissionFor($user->id, $payment),
                403,
                ManagerEditRequest::denialMessageFor($user->id, $payment)
            );
        } else {
            abort_unless($user->isAdmin(), 403, 'Managers cannot edit payments directly. Request admin edit instead.');
        }

        $validated = $request->validate([
            'rent_id' => 'required|exists:rents,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'status' => 'required|in:COMPLETED,PENDING,FAILED',
        ]);

        if ($user->isManager()) {
            $targetRent = Rent::with('unit.building')->findOrFail($validated['rent_id']);
            abort_unless((int) optional(optional($targetRent->unit)->building)->manager_id === (int) $user->id, 403, 'You can only assign payments to rents in your managed buildings.');
        }

        $oldValues = $payment->toArray();
        $payment->update($validated);
        app(AuditLogService::class)->log('payment_updated', $payment, $oldValues, $payment->toArray());

        return redirect()->route('payments.index')->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        if (! auth()->user()->hasPermission('payments.delete')) {
            return back()->with('error', 'Unauthorized action. Only admins can delete payments.');
        }

        app(AuditLogService::class)->log('payment_deleted', $payment, $payment->toArray(), null);
        $payment->delete();

        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
    }

    private function canDownloadReceipt($user, Payment $payment): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isManager()) {
            return (int) optional(optional(optional($payment->rent)->unit)->building)->manager_id === (int) $user->id;
        }

        if ($user->isTenant()) {
            return (int) optional(optional($payment->rent)->tenant)->user_id === (int) $user->id;
        }

        return false;
    }

    private function buildReceiptNumber(Payment $payment): string
    {
        $company = $payment->company
            ?? $payment->rent?->company
            ?? $payment->rent?->unit?->building?->company;

        $prefix = $this->receiptPrefixForCompanyName($company?->name);

        return $prefix.'-RCPT-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }

    private function receiptPrefixForCompanyName(?string $companyName): string
    {
        $normalized = trim((string) $companyName);
        if ($normalized === '') {
            return 'HOW';
        }

        $words = collect(preg_split('/[^A-Za-z0-9]+/', Str::upper($normalized)) ?: [])
            ->filter()
            ->reject(fn ($word) => in_array($word, ['AND', 'THE', 'OF', 'FOR', 'LIMITED', 'LTD'], true))
            ->values();

        if ($words->count() >= 2) {
            return $words
                ->take(4)
                ->map(fn ($word) => Str::substr($word, 0, 1))
                ->implode('');
        }

        return Str::upper(Str::substr($words->first() ?? 'HOWSEY', 0, 4));
    }
}
