<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentClaim;
use App\Support\AuditLogService;
use App\Support\RoleRequestNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentClaimController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403, 'You are not allowed to review payment claims.');

        $base = function () use ($user) {
            $query = PaymentClaim::query();
            if ($user->isManager()) {
                $query->whereHas('rent.unit.building', function ($q) use ($user) {
                    $q->where('manager_id', $user->id);
                });
            }

            return $query;
        };

        $status = strtoupper((string) $request->query('status', PaymentClaim::STATUS_PENDING));
        if (! in_array($status, $this->statuses(), true)) {
            $status = PaymentClaim::STATUS_PENDING;
        }

        $claims = $base()
            ->with(['rent.unit.building', 'tenant', 'submitter', 'confirmer', 'payment'])
            ->where('status', $status)
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $pendingCount = $base()->where('status', PaymentClaim::STATUS_PENDING)->count();

        return view('payment-claims.index', compact('claims', 'pendingCount', 'status'));
    }

    public function proof(PaymentClaim $claim)
    {
        $user = auth()->user();
        $this->authorizeReview($claim, $user, allowOwningTenant: true);

        abort_unless($claim->proof_path && Storage::disk('private')->exists($claim->proof_path), 404);

        $mime = Storage::disk('private')->mimeType($claim->proof_path) ?: 'application/octet-stream';

        return response()->file(Storage::disk('private')->path($claim->proof_path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($claim->proof_path).'"',
        ]);
    }

    public function confirm(Request $request, PaymentClaim $claim)
    {
        $user = auth()->user();
        $this->authorizeReview($claim, $user);

        if (! $claim->is_pending) {
            return back()->with('error', 'This payment claim has already been reviewed.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'review_note' => 'nullable|string|max:1000',
        ]);

        $rent = $claim->rent()->with(['tenant.user', 'unit.building'])->first();

        $payment = DB::transaction(function () use ($claim, $rent, $user, $validated) {
            // Complete the onboarding placeholder for this period instead of
            // inserting a second row, so the building financials stay clean.
            $payment = Payment::recordAgainst($rent, [
                'rent_id' => $claim->rent_id,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'due_date' => $rent?->next_due_date ?? $rent?->start_date,
                'payment_method' => $validated['payment_method'] ?: ($claim->payment_method ?: 'BANK_TRANSFER'),
                'status' => 'COMPLETED',
                'reference' => $claim->reference ?: 'CLAIM-'.$claim->id,
                'notes' => 'Confirmed from tenant payment claim #'.$claim->id.'.',
                'created_by' => $user->id,
            ]);

            $claim->update([
                'status' => PaymentClaim::STATUS_CONFIRMED,
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
                'review_note' => $validated['review_note'] ?? null,
                'payment_id' => $payment->id,
            ]);

            return $payment;
        });

        app(AuditLogService::class)->log('payment_claim_confirmed', $claim, null, $claim->fresh()->toArray());

        RoleRequestNotifier::notifyUser($rent?->tenant?->user, [
            'title' => 'Payment confirmed',
            'message' => 'Your payment of ₦'.number_format($payment->amount, 2).' has been confirmed and recorded on your account.',
            'url' => route('dashboard').'#payment-history',
            'request_type' => 'payment_confirmed',
            'created_by' => $user->name,
            'subject_id' => $claim->id,
        ], $user->id);

        return back()->with('success', 'Payment confirmed and recorded successfully.');
    }

    public function reject(Request $request, PaymentClaim $claim)
    {
        $user = auth()->user();
        $this->authorizeReview($claim, $user);

        if (! $claim->is_pending) {
            return back()->with('error', 'This payment claim has already been reviewed.');
        }

        $validated = $request->validate([
            'review_note' => 'required|string|max:1000',
        ]);

        $claim->update([
            'status' => PaymentClaim::STATUS_REJECTED,
            'confirmed_by' => $user->id,
            'confirmed_at' => now(),
            'review_note' => $validated['review_note'],
        ]);

        app(AuditLogService::class)->log('payment_claim_rejected', $claim, null, $claim->fresh()->toArray());

        $rent = $claim->rent()->with(['tenant.user'])->first();

        RoleRequestNotifier::notifyUser($rent?->tenant?->user, [
            'title' => 'Payment could not be confirmed',
            'message' => 'Your payment notification of ₦'.number_format((float) $claim->amount, 2).' could not be confirmed. '.$validated['review_note'],
            'url' => route('dashboard').'#payment-history',
            'request_type' => 'payment_rejected',
            'created_by' => $user->name,
            'subject_id' => $claim->id,
        ], $user->id);

        return back()->with('success', 'Payment claim rejected.');
    }

    private function statuses(): array
    {
        return [
            PaymentClaim::STATUS_PENDING,
            PaymentClaim::STATUS_CONFIRMED,
            PaymentClaim::STATUS_REJECTED,
        ];
    }

    private function authorizeReview(PaymentClaim $claim, $user, bool $allowOwningTenant = false): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isManager()) {
            $managerId = $claim->rent?->unit?->building?->manager_id;
            abort_unless($managerId === $user->id, 403, 'Unauthorized action.');

            return;
        }

        if ($allowOwningTenant && $user->isTenant()) {
            $ownerId = $claim->tenant?->user_id ?? null;
            abort_unless($ownerId === $user->id, 403, 'Unauthorized action.');

            return;
        }

        abort(403, 'Unauthorized action.');
    }
}
