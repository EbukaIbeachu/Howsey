@php
    $account = $account ?? null;
    $uid = $uid ?? 'new';
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Account Label <span class="text-muted small">(optional)</span></label>
        <input type="text" name="label" class="form-control" value="{{ old('label', $account->label ?? '') }}" placeholder="e.g. Main Collection Account">
    </div>
    <div class="col-md-6">
        <label class="form-label">Bank Name</label>
        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $account->bank_name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Account Name</label>
        <input type="text" name="account_name" class="form-control" value="{{ old('account_name', $account->account_name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Account Number</label>
        <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $account->account_number ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Branch <span class="text-muted small">(optional)</span></label>
        <input type="text" name="branch" class="form-control" value="{{ old('branch', $account->branch ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Display Order</label>
        <input type="number" name="sort_order" class="form-control" min="0" max="9999" value="{{ old('sort_order', $account->sort_order ?? 0) }}">
    </div>
    <div class="col-12">
        <label class="form-label">Note For Tenants <span class="text-muted small">(optional)</span></label>
        <textarea name="instructions" class="form-control" rows="2" placeholder="e.g. Use your unit number as the transfer narration.">{{ old('instructions', $account->instructions ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="account-active-{{ $uid }}" {{ old('is_active', $account->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="account-active-{{ $uid }}">Active (visible to tenants)</label>
        </div>
    </div>
    <div class="col-md-6">
        <input type="hidden" name="is_primary" value="0">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="account-primary-{{ $uid }}" {{ old('is_primary', $account->is_primary ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="account-primary-{{ $uid }}">Primary account (shown first)</label>
        </div>
    </div>
</div>
