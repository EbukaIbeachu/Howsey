@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Add New User</h1>

</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('users.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Account ID</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                        <div class="form-text" id="emailHelpText">Generated automatically for admin, manager, accountant, and tenant accounts. Make sure the user keeps this Account ID.</div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                        <div class="form-text">Use a login phone number the user will remember. Password recovery depends on this number.</div>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="role_id" class="form-label">Role</label>
                        <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" data-role-name="{{ strtolower($role->name) }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

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
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" id="createUserButton">Create User</button>
                    </div>
                </form>
            </div>
        </div>
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
