<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Support\AutoEmailGenerator;
use App\Support\CompanyOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function __construct(private readonly CompanyOnboardingService $onboardingService)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::withCount(['users', 'buildings'])->latest()->paginate(15);

        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        $this->authorize('create', Company::class);

        return view('companies.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Company::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:160', 'unique:companies,slug'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:40'],
            'receipt_header' => ['nullable', 'string', 'max:150'],
            'email_header' => ['nullable', 'string', 'max:150'],
            'admin_name' => ['required', 'string', 'max:150'],
            'admin_email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'admin_phone' => ['nullable', 'string', 'max:40'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'sample_dataset' => ['nullable', 'boolean'],
            'buildings_csv' => ['nullable', 'file', 'mimes:csv,txt'],
            'units_csv' => ['nullable', 'file', 'mimes:csv,txt'],
            'tenants_csv' => ['nullable', 'file', 'mimes:csv,txt'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);
        $validated['sample_dataset'] = $request->boolean('sample_dataset');
        $validated['email_header'] = trim((string) ($validated['email_header'] ?? '')) ?: $validated['name'];
        $validated['admin_email'] = AutoEmailGenerator::generate(
            $validated['admin_name'],
            'admin',
            AutoEmailGenerator::domainForSlug($validated['slug'])
        );

        $company = $this->onboardingService->createCompany($validated);

        return redirect()
            ->route('companies.edit', $company)
            ->with('success', 'Company created and onboarded successfully.')
            ->with('created_admin_account_id', $validated['admin_email'])
            ->with('created_admin_name', $validated['admin_name'])
            ->with('created_company_login_url', route('companies.login', $company->slug));
    }

    public function edit(Company $company)
    {
        $this->authorize('update', $company);

        $company->load(['settings', 'notificationSettings', 'notificationTemplates', 'documentTemplates', 'platformStatusChangedBy']);
        $workspaceAdmin = User::query()
            ->where('company_id', $company->id)
            ->whereHas('role', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['admin']);
            })
            ->orderBy('id')
            ->first();

        return view('companies.edit', compact('company', 'workspaceAdmin'));
    }

    public function update(Request $request, Company $company)
    {
        $this->authorize('update', $company);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:160', 'unique:companies,slug,'.$company->id],
            'platform_status' => ['required', 'in:active,inactive'],
            'platform_status_note' => ['nullable', 'string', 'max:2000'],
            'is_demo' => ['nullable', 'boolean'],
        ]);

        $platformStatus = $validated['platform_status'];
        $platformStatusNote = trim((string) ($validated['platform_status_note'] ?? '')) ?: null;
        $platformStatusChanged = $platformStatus !== ($company->platform_status ?: 'active')
            || $platformStatusNote !== $company->platformStatusNoteText();

        $updateData = [
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'is_demo' => $request->boolean('is_demo'),
            'platform_status' => $platformStatus,
            'platform_status_note' => $platformStatusNote,
        ];

        if ($platformStatusChanged) {
            $updateData['platform_status_changed_at'] = now();
            $updateData['platform_status_changed_by'] = $request->user()->id;
        }

        $company->update($updateData);

        return back()->with('success', 'Company profile updated.');
    }

    public function destroy(Request $request, Company $company)
    {
        $this->authorize('delete', $company);

        $companyName = $company->name;
        $companyId = (int) $company->id;
        $selectedCompanyId = (int) $request->session()->get('howsey_super_admin_view_company_id', 0);
        $fallbackCompanyId = Company::query()
            ->whereKeyNot($companyId)
            ->orderBy('id')
            ->value('id');
        $storedFiles = $this->collectStoredFiles($companyId);

        try {
            DB::transaction(function () use ($company, $companyId): void {
                $userIds = DB::table('users')
                    ->where('company_id', $companyId)
                    ->pluck('id');

                if ($userIds->isNotEmpty()) {
                    if (Schema::hasTable('notifications')) {
                        DB::table('notifications')
                            ->where('notifiable_type', User::class)
                            ->whereIn('notifiable_id', $userIds)
                            ->delete();
                    }

                    if (Schema::hasTable('push_subscriptions')) {
                        DB::table('push_subscriptions')
                            ->whereIn('user_id', $userIds)
                            ->delete();
                    }

                    if (Schema::hasTable('sessions')) {
                        DB::table('sessions')
                            ->whereIn('user_id', $userIds)
                            ->delete();
                    }

                    if (Schema::hasTable('password_recovery_requests')) {
                        DB::table('password_recovery_requests')
                            ->where(function ($query) use ($userIds) {
                                $query->whereIn('user_id', $userIds)
                                    ->orWhereIn('reviewer_id', $userIds);
                            })
                            ->delete();
                    }

                    if (Schema::hasTable('manager_edit_requests')) {
                        DB::table('manager_edit_requests')
                            ->where(function ($query) use ($userIds) {
                                $query->whereIn('user_id', $userIds)
                                    ->orWhereIn('admin_id', $userIds);
                            })
                            ->delete();
                    }
                }

                if (Schema::hasTable('manager_edit_requests')) {
                    DB::table('manager_edit_requests')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('edit_requests')) {
                    DB::table('edit_requests')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('audit_logs')) {
                    DB::table('audit_logs')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('accounting_entries')) {
                    DB::table('accounting_entries')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('maintenance_requests')) {
                    DB::table('maintenance_requests')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('payments')) {
                    DB::table('payments')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('rents')) {
                    DB::table('rents')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('tenants')) {
                    DB::table('tenants')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('units')) {
                    DB::table('units')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('announcements')) {
                    DB::table('announcements')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('buildings')) {
                    DB::table('buildings')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('notification_templates')) {
                    DB::table('notification_templates')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('document_templates')) {
                    DB::table('document_templates')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('notification_settings')) {
                    DB::table('notification_settings')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if (Schema::hasTable('company_settings')) {
                    DB::table('company_settings')
                        ->where('company_id', $companyId)
                        ->delete();
                }

                if ($userIds->isNotEmpty()) {
                    DB::table('users')
                        ->whereIn('id', $userIds)
                        ->delete();
                }

                $company->delete();
            });
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Company deletion failed. Please try again.');
        }

        if ($selectedCompanyId === $companyId) {
            if ($fallbackCompanyId) {
                $request->session()->put('howsey_super_admin_view_company_id', (int) $fallbackCompanyId);
            } else {
                $request->session()->forget('howsey_super_admin_view_company_id');
            }
        }

        if (! empty($storedFiles)) {
            Storage::disk('public')->delete($storedFiles);
            Storage::disk('private')->delete($storedFiles);
        }

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company "'.$companyName.'" deleted successfully.');
    }

    private function collectStoredFiles(int $companyId): array
    {
        $storedFiles = [
            Schema::hasTable('company_settings')
                ? DB::table('company_settings')
                    ->where('company_id', $companyId)
                    ->value('logo_path')
                : null,
        ];

        if (Schema::hasTable('buildings') && Schema::hasColumn('buildings', 'image_path')) {
            $storedFiles = array_merge(
                $storedFiles,
                DB::table('buildings')
                    ->where('company_id', $companyId)
                    ->whereNotNull('image_path')
                    ->pluck('image_path')
                    ->all()
            );
        }

        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'profile_image')) {
            $storedFiles = array_merge(
                $storedFiles,
                DB::table('tenants')
                    ->where('company_id', $companyId)
                    ->whereNotNull('profile_image')
                    ->pluck('profile_image')
                    ->all()
            );
        }

        if (Schema::hasTable('rents') && Schema::hasColumn('rents', 'signed_agreement_path')) {
            $storedFiles = array_merge(
                $storedFiles,
                DB::table('rents')
                    ->where('company_id', $companyId)
                    ->whereNotNull('signed_agreement_path')
                    ->pluck('signed_agreement_path')
                    ->all()
            );
        }

        return collect($storedFiles)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
