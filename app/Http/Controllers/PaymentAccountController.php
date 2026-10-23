<?php

namespace App\Http\Controllers;

use App\Models\PaymentAccount;
use App\Support\AuditLogService;
use Illuminate\Http\Request;

class PaymentAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $this->authorizeManager();

        $validated = $this->validated($request);

        $account = PaymentAccount::create($validated);

        $this->syncPrimary($account);

        app(AuditLogService::class)->log('payment_account_created', $account, null, $account->toArray());

        return back()->with('success', 'Receiving account added successfully.');
    }

    public function update(Request $request, PaymentAccount $paymentAccount)
    {
        $this->authorizeManager();

        $before = $paymentAccount->toArray();
        $paymentAccount->update($this->validated($request));

        $this->syncPrimary($paymentAccount);

        app(AuditLogService::class)->log('payment_account_updated', $paymentAccount, $before, $paymentAccount->fresh()->toArray());

        return back()->with('success', 'Receiving account updated successfully.');
    }

    public function destroy(PaymentAccount $paymentAccount)
    {
        $this->authorizeManager();

        $before = $paymentAccount->toArray();
        $paymentAccount->delete();

        app(AuditLogService::class)->log('payment_account_deleted', null, $before, null);

        return back()->with('success', 'Receiving account removed.');
    }

    private function authorizeManager(): void
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->hasPermission('settings.edit'), 403, 'You are not allowed to manage receiving accounts.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:100',
            'bank_name' => 'required|string|max:150',
            'account_name' => 'required|string|max:150',
            'account_number' => 'required|string|max:40',
            'branch' => 'nullable|string|max:150',
            'instructions' => 'nullable|string|max:1000',
            'is_primary' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        // The "is_active" checkbox is only meaningful on the edit form; new
        // accounts default to active so they show up for tenants immediately.
        $validated['is_active'] = $request->exists('is_active')
            ? $request->boolean('is_active')
            : true;

        $validated['is_primary'] = $request->boolean('is_primary');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        return $validated;
    }

    private function syncPrimary(PaymentAccount $account): void
    {
        if (! $account->is_primary) {
            return;
        }

        PaymentAccount::query()
            ->whereKeyNot($account->getKey())
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }
}
