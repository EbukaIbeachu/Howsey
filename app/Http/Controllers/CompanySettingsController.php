<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanySettingsController extends Controller
{
    private const ALLOWED_PRIMARY_COLORS = [
        '#7c3aed',
        '#2563eb',
        '#059669',
        '#dc2626',
        '#ea580c',
        '#d97706',
        '#db2777',
        '#0891b2',
        '#4f46e5',
        '#65a30d',
        '#9333ea',
        '#475569',
        '#0f766e',
        '#1d4ed8',
        '#be123c',
        '#7e22ce',
        '#c2410c',
        '#15803d',
        '#b45309',
        '#334155',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit()
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->hasPermission('settings.view'), 403);

        if (! $user->company_id) {
            return redirect()
                ->route('companies.index')
                ->with('error', 'Platform owners manage company branding from the Companies area.');
        }

        $company = $user->company()->with([
            'settings',
            'notificationSettings',
            'notificationTemplates',
            'documentTemplates',
        ])->firstOrFail();

        return view('companies.settings', compact('company'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->hasPermission('settings.edit'), 403);

        if (! $user->company_id) {
            return redirect()
                ->route('companies.index')
                ->with('error', 'Platform owners manage company branding from the Companies area.');
        }

        $company = $user->company()->with([
            'settings',
            'notificationSettings',
            'notificationTemplates',
            'documentTemplates',
        ])->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'workspace_status' => ['nullable', Rule::in(['active', 'inactive'])],
            'logo' => ['nullable', 'image', 'max:2048'],
            'primary_color' => ['nullable', 'string', 'max:20', Rule::in(self::ALLOWED_PRIMARY_COLORS)],
            'email_header' => ['nullable', 'string', 'max:150'],
            'receipt_header' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:40'],
            'email_enabled' => ['nullable', 'boolean'],
            'send_overdue_rent_reminders' => ['nullable', 'boolean'],
            'send_lease_expiry_reminders' => ['nullable', 'boolean'],
            'lease_expiry_days_notice' => ['nullable', 'integer', 'min:1', 'max:365'],
            'from_name' => ['nullable', 'string', 'max:150'],
            'from_email' => ['nullable', 'email', 'max:150'],
        ]);

        $status = $validated['workspace_status'] ?? $company->status;

        $company->update([
            'name' => $validated['name'],
            'status' => $status,
        ]);

        $settingsData = [
            'primary_color' => $validated['primary_color'] ?? '#7c3aed',
            'email_header' => trim((string) ($validated['email_header'] ?? '')) ?: $company->name,
            'receipt_header' => $validated['receipt_header'] ?? 'Official Payment Receipt',
            'address' => $validated['address'] ?? null,
            'support_email' => $validated['support_email'] ?? null,
            'support_phone' => $validated['support_phone'] ?? null,
        ];

        if ($request->hasFile('logo')) {
            $settingsData['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        $company->settings()->updateOrCreate(['company_id' => $company->id], $settingsData);

        $company->notificationSettings()->updateOrCreate(
            ['company_id' => $company->id],
            [
                'email_enabled' => $request->boolean('email_enabled'),
                'send_overdue_rent_reminders' => $request->boolean('send_overdue_rent_reminders'),
                'send_lease_expiry_reminders' => $request->boolean('send_lease_expiry_reminders'),
                'lease_expiry_days_notice' => $validated['lease_expiry_days_notice'] ?? 30,
                'from_name' => $validated['from_name'] ?? $company->name,
                'from_email' => $validated['from_email'] ?? null,
            ]
        );

        foreach (['overdue_rent', 'lease_expiry'] as $type) {
            NotificationTemplate::updateOrCreate(
                ['company_id' => $company->id, 'type' => $type, 'channel' => 'email'],
                [
                    'subject' => $request->input("notification_templates.{$type}.subject"),
                    'body' => $request->input("notification_templates.{$type}.body"),
                ]
            );
        }

        foreach (['rent_receipt', 'arrears_notice', 'lease_renewal_notice'] as $type) {
            DocumentTemplate::updateOrCreate(
                ['company_id' => $company->id, 'type' => $type],
                [
                    'subject' => $request->input("document_templates.{$type}.subject"),
                    'body_html' => $request->input("document_templates.{$type}.body_html"),
                ]
            );
        }

        $statusMessage = $status === 'inactive'
            ? ' Workspace access is now deactivated for staff and tenants until a company admin reactivates it.'
            : ' Workspace access is active.';

        return back()->with('success', 'Company settings updated successfully.'.$statusMessage);
    }
}
