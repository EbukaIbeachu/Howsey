<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        if (! $user->isTenant() || ! $user->tenant) {
            return response()->json(['message' => 'Unauthorized or Tenant profile not found'], 403);
        }

        $tenant = $user->tenant;
        $rent = $tenant->currentRent;
        $recentPayments = $tenant->payments()->latest('payment_date')->take(5)->get();
        $balance = $rent ? $rent->balance : 0;

        return response()->json([
            'tenant' => $tenant,
            'current_lease' => $rent ? $rent->load('unit.building') : null,
            'recent_payments' => $recentPayments,
            'balance' => $balance,
        ]);
    }

    public function payments(Request $request)
    {
        $user = $request->user();
        if (! $user->isTenant() || ! $user->tenant) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payments = $user->tenant->payments()
            ->with('rent.unit')
            ->latest('payment_date')
            ->paginate(20);

        return response()->json($payments);
    }
}
