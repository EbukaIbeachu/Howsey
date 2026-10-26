@extends('layouts.app')

@section('content')
<x-ui.page-header title="Add New User" />

<div class="row">
    <div class="col-md-8">
        <x-ui.card>
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                
                <x-ui.form.input name="name" label="Full Name" required />

                {{-- The Account ID hint is rewritten in place by the page script through
                     getElementById('emailHelpText'), so it stays hand-written markup to
                     keep that id (x-ui.form.input renders an id-less hint). --}}
                <div class="ui-field">
                    <label for="email" class="ui-label">Account ID</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           class="ui-input @error('email') is-invalid @enderror">
                    <span class="ui-hint" id="emailHelpText">Generated automatically for admin, manager, accountant, and tenant accounts. Make sure the user keeps this Account ID.</span>
                    @error('email')
                        <span class="ui-error" id="email-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <x-ui.form.input
                    name="phone"
                    label="Phone Number"
                    hint="Use a login phone number the user will remember. Password recovery depends on this number."
                />

                <x-ui.form.select name="role_id" label="Role" required placeholder="Select Role">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" data-role-name="{{ strtolower($role->name) }}" @selected(old('role_id') == $role->id)>
                            {{ ucfirst($role->name) }}
                        </option>
                    @endforeach
                </x-ui.form.select>

                <div class="border rounded-3 p-3 mb-3 bg-light d-none" id="tenantAccountSection">
                    <div class="fw-semibold mb-2">Tenant Account Check</div>
                    <p class="text-muted small mb-3">A tenant login can only be created for a tenant who is already assigned to a building and unit.</p>

                    <div class="mb-3">
                        <label class="form-label d-block">Has this tenant already been registered to a building/unit?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tenant_registration_state" id="tenant_registered_yes" value="yes" {{ old('tenant_registration_state', 'yes') === 'yes' ? 'checked' : '' }}>
                            <label class="form-check-label" for="tenant_registered_yes">Yes, already registered</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tenant_registration_state" id="tenant_registered_no" value="no" {{ old('tenant_registration_state') === 'no' ? 'checked' : '' }}>
                            <label class="form-check-label" for="tenant_registered_no">No, not yet registered</label>
                        </div>
                    </div>

                    <div class="alert alert-warning d-none mb-3" id="tenantAccountBlockedMessage">
                        Register the tenant in the `Tenants` module first, assign the building/unit, then return here to create the login account.
                    </div>

                    <div class="mb-0">
                        <label for="tenant_id" class="form-label">Registered Tenant</label>
                        <select class="form-select @error('tenant_id') is-invalid @enderror" id="tenant_id" name="tenant_id">
                            <option value="">Select Registered Tenant</option>
                            @foreach($availableTenants as $tenant)
                                <option
                                    value="{{ $tenant->id }}"
                                    data-name="{{ $tenant->full_name }}"
                                    data-email="{{ $tenant->email }}"
                                    data-phone="{{ $tenant->phone }}"
                                    {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}
                                >
                                    {{ $tenant->full_name }} - {{ $tenant->unit->building->name }} / Unit {{ $tenant->unit->unit_number }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text" id="tenantHelpText">
                            @if($availableTenants->isEmpty())
                                No eligible tenant records are available right now. Register the tenant to a building/unit first.
                            @else
                                Selecting a tenant will pull the account details from the registered tenant record.
                            @endif
                        </div>
                        @error('tenant_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.input name="password" type="password" label="Password" required />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="password_confirmation" type="password" label="Confirm Password" required />
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <x-ui.button type="submit" variant="primary" id="createUserButton">Create User</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const accountDomain = @json(\App\Support\AutoEmailGenerator::domainForCompany($currentCompany ?? null));
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');
        const roleSelect = document.getElementById('role_id');
        const emailHelpText = document.getElementById('emailHelpText');
        const tenantAccountSection = document.getElementById('tenantAccountSection');
        const tenantSelect = document.getElementById('tenant_id');
        const tenantRegisteredYes = document.getElementById('tenant_registered_yes');
        const tenantRegisteredNo = document.getElementById('tenant_registered_no');
        const tenantAccountBlockedMessage = document.getElementById('tenantAccountBlockedMessage');
        const createUserButton = document.getElementById('createUserButton');
        const generatedRoles = new Set(['admin', 'manager', 'accountant']);

        function generateEmailFromName(name) {
            const asciiName = name.normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toLowerCase();
            const parts = asciiName.split(/\s+/).map(part => part.replace(/[^a-z0-9]/g, '')).filter(Boolean);

            if (parts.length === 0) {
                return '';
            }

            if (parts.length === 1) {
                return `${parts[0]}@${accountDomain}`;
            }

            return `${parts[0].charAt(0)}.${parts[parts.length - 1]}@${accountDomain}`;
        }

        function getSelectedRoleName() {
            return roleSelect.options[roleSelect.selectedIndex]?.dataset?.roleName || '';
        }

        function syncTenantSelection() {
            const selectedTenant = tenantSelect.options[tenantSelect.selectedIndex];
            const tenantIsSelected = tenantSelect.value !== '';
            const canCreateTenantAccount = tenantRegisteredYes.checked && tenantIsSelected;
            const selectedRole = getSelectedRoleName();
            const isTenantRole = selectedRole === 'tenant';

            tenantAccountBlockedMessage.classList.toggle('d-none', !isTenantRole || !tenantRegisteredNo.checked);
            tenantSelect.disabled = !isTenantRole || tenantRegisteredNo.checked;
            createUserButton.disabled = isTenantRole && !canCreateTenantAccount;

            if (!isTenantRole) {
                nameInput.readOnly = false;
                phoneInput.readOnly = false;
                return;
            }

            nameInput.readOnly = true;
            phoneInput.readOnly = true;

            if (tenantIsSelected) {
                nameInput.value = selectedTenant.dataset.name || '';
                emailInput.value = selectedTenant.dataset.email || '';
                phoneInput.value = selectedTenant.dataset.phone || '';
            } else {
                nameInput.value = '';
                emailInput.value = '';
                phoneInput.value = '';
            }
        }

        function syncEmailField() {
            const selectedRole = getSelectedRoleName();
            const usesAutoEmail = generatedRoles.has(selectedRole);
            const isTenantRole = selectedRole === 'tenant';

            tenantAccountSection.classList.toggle('d-none', !isTenantRole);
            emailInput.readOnly = usesAutoEmail || isTenantRole;
            emailInput.required = !usesAutoEmail;
            emailHelpText.textContent = usesAutoEmail
                ? 'Generated automatically from the full name for this role.'
                : (isTenantRole
                    ? 'Selected automatically from the registered tenant record.'
                    : 'Enter the account ID for this account.');

            if (usesAutoEmail) {
                emailInput.value = generateEmailFromName(nameInput.value);
            }

            syncTenantSelection();
        }

        nameInput.addEventListener('input', syncEmailField);
        roleSelect.addEventListener('change', syncEmailField);
        tenantSelect.addEventListener('change', syncTenantSelection);
        tenantRegisteredYes.addEventListener('change', syncTenantSelection);
        tenantRegisteredNo.addEventListener('change', syncTenantSelection);
        syncEmailField();
    });
</script>
@endpush
