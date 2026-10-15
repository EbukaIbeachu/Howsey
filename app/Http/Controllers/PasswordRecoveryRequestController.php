<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PasswordRecoveryRequest;
use App\Models\User;
use App\Support\CurrentCompany;
use App\Support\RoleRequestNotifier;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordRecoveryRequestController extends Controller
{
    public function store(Request $request)
    {
        $rootCompanyAdminAccess = $request->boolean('root_company_admin_access');

        $validated = $request->validateWithBag('forgotPassword', [
            'recovery_phone' => ['required', 'string', 'max:20'],
            'recovery_workspace' => [$rootCompanyAdminAccess ? 'required' : 'nullable', 'string', 'max:255'],
        ]);

        $phone = $validated['recovery_phone'];
        $normalizedPhone = $this->normalizePhone($phone);
        if ($normalizedPhone === '') {
            return back()
                ->withErrors(['recovery_phone' => 'Enter a valid login phone number.'], 'forgotPassword')
                ->withInput();
        }

        $company = $rootCompanyAdminAccess
            ? $this->resolveWorkspaceCompany($validated['recovery_workspace'] ?? null)
            : app(CurrentCompany::class)->get();

        if ($rootCompanyAdminAccess && ! $company) {
            return back()
                ->withErrors(['recovery_workspace' => 'Enter a valid company workspace link or slug.'], 'forgotPassword')
                ->withInput();
        }

        $matches = $this->findRecoveryCandidates($normalizedPhone, $company, $rootCompanyAdminAccess);

        if ($matches->isEmpty()) {
            return back()
                ->withErrors([
                    'recovery_phone' => $rootCompanyAdminAccess
                        ? 'No workspace admin account matches that login phone number in the selected company workspace.'
                        : 'No user account matches that login phone number in this workspace.',
                ], 'forgotPassword')
                ->withInput();
        }

        if ($matches->count() > 1) {
            return back()
                ->withErrors([
                    'recovery_phone' => $rootCompanyAdminAccess
                        ? 'More than one workspace admin account matches that login phone number. Update the account phone numbers before using phone-only recovery.'
                        : 'More than one user account matches that login phone number in this workspace. Ask an admin to correct the duplicate phone numbers first.',
                ], 'forgotPassword')
                ->withInput();
        }

        $user = $matches->first();

        $existingPending = PasswordRecoveryRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (! $existingPending) {
            $recoveryRequest = PasswordRecoveryRequest::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone ?: $phone,
                'status' => 'pending',
            ]);

            $payload = [
                'title' => 'Password recovery request',
                'message' => 'A password recovery request was submitted for login phone '.$phone.' and matched to account ID '.$user->email.'.',
                'url' => route('password-recovery-requests.index'),
                'request_type' => 'password_recovery',
                'created_by' => $user->email,
                'subject_id' => $recoveryRequest->id,
            ];

            if ($rootCompanyAdminAccess) {
                RoleRequestNotifier::notifyUsers(
                    User::query()
                        ->whereHas('role', function (Builder $query) {
                            $query->whereRaw('LOWER(name) = ?', ['super_admin']);
                        })
                        ->get(),
                    $payload,
                    $user->id
                );
            } elseif ($user->isTenant()) {
                $manager = optional(optional(optional($user->tenant)->unit)->building)->manager;

                if ($manager) {
                    RoleRequestNotifier::notifyUser($manager, $payload, $user->id);
                } else {
                    RoleRequestNotifier::notifyAdmins($payload, $user->id);
                }
            } else {
                RoleRequestNotifier::notifyAdmins($payload, $user->id);
            }
        }

        if ($rootCompanyAdminAccess) {
            return back()->with('success', 'Admin password recovery request submitted. A HOWSEY super admin will review the company workspace and matched login phone details.');
        }

        if ($user->isTenant()) {
            return back()->with('success', 'Password recovery request submitted. Your building manager will review your matched account and login phone number.');
        }

        return back()->with('success', 'Password recovery request submitted. An admin will review your matched account and login phone number.');
    }

    public function index()
    {
        abort_unless($this->canReview(auth()->user()), 403, 'Unauthorized action.');

        $requests = $this->visibleRequestsQuery(auth()->user())
            ->with(['user.role', 'user.company', 'user.tenant.unit.building.manager', 'reviewer'])
            ->latest()
            ->paginate(15);

        return view('password-recovery-requests.index', compact('requests'));
    }

    public function approve(Request $request, PasswordRecoveryRequest $passwordRecoveryRequest)
    {
        abort_unless($this->canReviewRequest(auth()->user(), $passwordRecoveryRequest), 403, 'Unauthorized action.');

        if ($passwordRecoveryRequest->status !== 'pending') {
            return back()->with('error', 'This recovery request has already been reviewed.');
        }

        $validated = $request->validate([
            'temporary_password' => ['required', 'string', 'min:8', 'confirmed'],
            'reviewer_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $passwordRecoveryRequest->user ?: User::where('email', $passwordRecoveryRequest->email)->first();

        if (! $user) {
            return back()->with('error', 'No user account matches the submitted account ID for this request.');
        }

        $user->update([
            'password' => Hash::make($validated['temporary_password']),
        ]);

        $passwordRecoveryRequest->update([
            'user_id' => $user->id,
            'status' => 'approved',
            'reviewer_id' => auth()->id(),
            'reviewer_notes' => $validated['reviewer_notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Recovery request approved and a new password has been set for the user.');
    }

    public function reject(Request $request, PasswordRecoveryRequest $passwordRecoveryRequest)
    {
        abort_unless($this->canReviewRequest(auth()->user(), $passwordRecoveryRequest), 403, 'Unauthorized action.');

        if ($passwordRecoveryRequest->status !== 'pending') {
            return back()->with('error', 'This recovery request has already been reviewed.');
        }

        $validated = $request->validate([
            'reviewer_notes' => ['required', 'string', 'max:1000'],
        ]);

        $passwordRecoveryRequest->update([
            'status' => 'rejected',
            'reviewer_id' => auth()->id(),
            'reviewer_notes' => $validated['reviewer_notes'],
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Recovery request rejected.');
    }

    private function canReview(?User $user): bool
    {
        return $user?->isSuperAdmin() || $user?->isAdmin() || $user?->isManager();
    }

    private function visibleRequestsQuery(User $reviewer)
    {
        $query = PasswordRecoveryRequest::query();

        if ($reviewer->isSuperAdmin()) {
            return $query->whereHas('user.role', function (Builder $roleQuery) {
                $roleQuery->whereRaw('LOWER(name) = ?', ['admin']);
            });
        }

        if ($reviewer->isAdmin()) {
            return $query->where(function (Builder $builder) {
                $builder
                    ->whereDoesntHave('user.role', function (Builder $roleQuery) {
                        $roleQuery->whereRaw('LOWER(name) = ?', ['tenant']);
                    })
                    ->orWhereDoesntHave('user.tenant.unit.building.manager');
            });
        }

        return $query->whereHas('user.tenant.unit.building.manager', function (Builder $managerQuery) use ($reviewer) {
            $managerQuery->where('users.id', $reviewer->id);
        });
    }

    private function canReviewRequest(?User $reviewer, PasswordRecoveryRequest $passwordRecoveryRequest): bool
    {
        if (! $reviewer || ! $this->canReview($reviewer)) {
            return false;
        }

        return $this->visibleRequestsQuery($reviewer)
            ->whereKey($passwordRecoveryRequest->id)
            ->exists();
    }

    private function resolveWorkspaceCompany(?string $workspace): ?Company
    {
        $slug = $this->extractWorkspaceSlug($workspace);

        if (! $slug) {
            return null;
        }

        return Company::where('slug', $slug)->first();
    }

    private function extractWorkspaceSlug(?string $workspace): ?string
    {
        $workspace = trim((string) $workspace);

        if ($workspace === '') {
            return null;
        }

        if (filter_var($workspace, FILTER_VALIDATE_URL)) {
            $path = trim((string) parse_url($workspace, PHP_URL_PATH), '/');
            $segments = array_values(array_filter(explode('/', $path)));

            if (($segments[0] ?? null) === 'c' && isset($segments[1])) {
                return $segments[1];
            }

            return $segments[count($segments) - 1] ?? null;
        }

        $workspace = trim($workspace, '/');

        if (str_starts_with($workspace, 'c/')) {
            $segments = explode('/', $workspace);

            return $segments[1] ?? null;
        }

        return $workspace;
    }

    private function findRecoveryCandidates(string $normalizedPhone, ?Company $company, bool $rootCompanyAdminAccess): Collection
    {
        $query = User::query()
            ->with(['role', 'tenant.unit.building.manager', 'company'])
            ->whereNotNull('phone');

        if ($rootCompanyAdminAccess) {
            $query->where('company_id', $company?->id)
                ->whereHas('role', function (Builder $roleQuery) {
                    $roleQuery->whereRaw('LOWER(name) = ?', ['admin']);
                });
        } elseif ($company) {
            $query->where('company_id', $company->id);
        } else {
            $query->whereHas('role', function (Builder $roleQuery) {
                $roleQuery->whereRaw('LOWER(name) <> ?', ['super_admin']);
            });
        }

        return $query->get()->filter(function (User $user) use ($normalizedPhone) {
            return $this->normalizePhone($user->phone) === $normalizedPhone;
        })->values();
    }

    private function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?: '';
    }
}
