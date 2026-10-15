<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user()->load(['role', 'tenant']);
        $tenant = $this->resolveTenantForUser($user);

        return view('account.edit', [
            'user' => $user,
            'tenant' => $tenant,
            'phoneColumnAvailable' => Schema::hasColumn('users', 'phone'),
            'tenantProfileImageAvailable' => Schema::hasColumn('tenants', 'profile_image'),
        ]);
    }

    public function updateTenantProfileImage(Request $request)
    {
        if (! Schema::hasColumn('tenants', 'profile_image')) {
            return back()->with('error', 'Profile image uploads are not available until the tenants profile_image column is installed.');
        }

        $user = $request->user()->load(['role', 'tenant']);
        $tenant = $this->resolveTenantForUser($user);

        if (! $user->isTenant() || ! $tenant) {
            return back()->with('error', 'Only tenant accounts can update a tenant profile image.');
        }

        $validated = $request->validate([
            'profile_image' => ['required', 'image', 'max:2048'],
        ]);

        if ($tenant->profile_image) {
            Storage::disk('public')->delete($tenant->profile_image);
        }

        $path = $request->file('profile_image')->store('tenants', 'public');

        $tenant->update([
            'profile_image' => $path,
        ]);

        return back()->with('success', 'Profile image updated successfully.');
    }

    public function updatePhone(Request $request)
    {
        if (! Schema::hasColumn('users', 'phone')) {
            return back()->with('error', 'Phone number updates are not available until the users phone column is installed.');
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $request->user()->update([
            'phone' => $validated['phone'],
        ]);

        return back()->with('success', 'Phone number updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    private function resolveTenantForUser($user): ?Tenant
    {
        $tenant = $user->tenant;

        if (! $tenant && $user->isTenant()) {
            $tenant = Tenant::where('email', $user->email)->first();

            if ($tenant && $tenant->user_id !== $user->id) {
                $tenant->update(['user_id' => $user->id]);
            }
        }

        return $tenant;
    }
}
